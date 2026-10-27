package ug.ac.mubs.eventpass.data

import androidx.room.withTransaction
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.async
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import ug.ac.mubs.eventpass.domain.PasswordHasher
import ug.ac.mubs.eventpass.domain.TicketCodes
import ug.ac.mubs.eventpass.domain.Validation

class AppException(message: String) : Exception(message)

class EventPassRepository(private val db: EventPassDatabase, scope: CoroutineScope) {
    private val users = db.userDao()
    private val events = db.eventDao()
    private val bookings = db.bookingDao()

    // Demo accounts must exist before the first login attempt is checked.
    private val seeding = scope.async { DemoDataSeeder.seedIfEmpty(db) }

    // ---------- Accounts ----------

    suspend fun register(
        fullName: String,
        email: String,
        studentNumber: String?,
        password: String,
        role: Role,
        organizerCode: String?,
    ): Result<User> = runCatching {
        val cleanEmail = email.trim().lowercase()
        if (fullName.isBlank()) throw AppException("Enter your full name")
        if (!Validation.isValidEmail(cleanEmail)) throw AppException("Enter a valid email address")
        Validation.passwordProblem(password)?.let { throw AppException(it) }
        if (role == Role.STUDENT && studentNumber.isNullOrBlank()) throw AppException("Enter your student number")
        if (role == Role.ORGANIZER && organizerCode?.trim() != ORGANIZER_ACCESS_CODE) {
            throw AppException("Invalid organizer access code. Ask the MUBS events office for one.")
        }
        seeding.await()
        withContext(Dispatchers.IO) {
            if (users.findByEmail(cleanEmail) != null) throw AppException("An account with this email already exists")
            val salt = PasswordHasher.newSalt()
            val user = User(
                fullName = fullName.trim(),
                email = cleanEmail,
                studentNumber = studentNumber?.trim()?.takeIf { it.isNotEmpty() },
                passwordHash = PasswordHasher.hash(password, salt),
                salt = salt,
                role = role,
            )
            user.copy(id = users.insert(user))
        }
    }

    suspend fun login(email: String, password: String): Result<User> = runCatching {
        seeding.await()
        withContext(Dispatchers.IO) {
            val user = users.findByEmail(email.trim())
            if (user == null || !PasswordHasher.verify(password, user.salt, user.passwordHash)) {
                throw AppException("Incorrect email or password")
            }
            user
        }
    }

    fun observeUser(id: Long): Flow<User?> = users.observeById(id)

    // ---------- Events ----------

    fun observeUpcomingEvents(): Flow<List<EventWithStats>> = events.observeUpcoming(System.currentTimeMillis())

    fun observeEvent(id: Long): Flow<EventWithStats?> = events.observeById(id)

    fun observeOrganizerEvents(organizerId: Long): Flow<List<EventWithStats>> = events.observeByOrganizer(organizerId)

    suspend fun getEvent(id: Long): Event? = events.findById(id)

    suspend fun saveEvent(event: Event, organizerId: Long): Result<Long> = runCatching {
        if (event.title.isBlank()) throw AppException("Enter the event name")
        if (event.venue.isBlank()) throw AppException("Enter the venue")
        if (event.capacity <= 0) throw AppException("Capacity must be at least 1")
        if (event.priceUgx < 0) throw AppException("Price cannot be negative")
        if (event.endTime <= event.startTime) throw AppException("The event must end after it starts")
        db.withTransaction {
            if (event.id == 0L) {
                events.insert(event.copy(organizerId = organizerId))
            } else {
                val existing = events.findById(event.id) ?: throw AppException("Event not found")
                if (existing.organizerId != organizerId) throw AppException("You can only edit your own events")
                val booked = bookings.countConfirmed(event.id)
                if (event.capacity < booked) throw AppException("Capacity cannot be below the $booked tickets already issued")
                events.update(event.copy(organizerId = organizerId, createdAt = existing.createdAt))
                event.id
            }
        }
    }

    // ---------- Bookings & tickets ----------

    fun observeMyBookings(userId: Long): Flow<List<BookingWithEvent>> = bookings.observeForUser(userId)

    fun observeMyBookingFor(userId: Long, eventId: Long): Flow<Booking?> = bookings.observeForUserAndEvent(userId, eventId)

    fun observeBooking(bookingId: Long): Flow<BookingWithEvent?> = bookings.observeWithEvent(bookingId)

    fun observeAttendees(eventId: Long): Flow<List<Attendee>> = bookings.observeAttendees(eventId)

    /**
     * Reserves a place. Free events are confirmed (and a ticket issued) immediately;
     * paid events stay PENDING_PAYMENT until a payment confirmation arrives.
     */
    suspend fun bookEvent(userId: Long, eventId: Long): Result<Booking> = runCatching {
        db.withTransaction {
            val event = events.findById(eventId) ?: throw AppException("Event not found")
            if (event.isCancelled) throw AppException("This event has been cancelled")
            if (event.endTime < System.currentTimeMillis()) throw AppException("This event has already ended")
            val existing = bookings.findForUserAndEvent(userId, eventId)
            if (existing != null && existing.status != BookingStatus.CANCELLED) return@withTransaction existing
            if (bookings.countConfirmed(eventId) >= event.capacity) throw AppException("Sorry, this event is sold out")

            val now = System.currentTimeMillis()
            val booking = Booking(
                id = existing?.id ?: 0,
                eventId = eventId,
                userId = userId,
                status = if (event.isFree) BookingStatus.CONFIRMED else BookingStatus.PENDING_PAYMENT,
                amountUgx = event.priceUgx,
                ticketCode = if (event.isFree) newUniqueTicketCode() else null,
                bookedAt = now,
                confirmedAt = if (event.isFree) now else null,
            )
            if (existing == null) booking.copy(id = bookings.insert(booking)) else booking.also { bookings.update(it) }
        }
    }

    /**
     * Simulated mobile money payment for the prototype. A real deployment would call the
     * MTN MoMo / Airtel Money collection API and confirm only on its success callback.
     * PIN 0000 simulates a declined payment so the failure path can be demonstrated.
     */
    suspend fun payForBooking(bookingId: Long, phone: String, pin: String): Result<Booking> = runCatching {
        val local = Validation.normaliseUgandanPhone(phone) ?: throw AppException("Enter a valid Ugandan mobile number")
        val network = Validation.networkFor(local) ?: throw AppException("Only MTN and Airtel numbers are supported")
        if (pin.length !in 4..5 || !pin.all { it.isDigit() }) throw AppException("Enter your 4 or 5 digit mobile money PIN")

        delay(2_000) // waiting for the network to respond
        if (pin == "0000") throw AppException("Payment declined by ${network.label}. No money was deducted.")

        db.withTransaction {
            val booking = bookings.findById(bookingId) ?: throw AppException("Booking not found")
            if (booking.status == BookingStatus.CONFIRMED) return@withTransaction booking
            if (booking.status != BookingStatus.PENDING_PAYMENT) throw AppException("This booking was cancelled")
            val event = events.findById(booking.eventId) ?: throw AppException("Event not found")
            if (bookings.countConfirmed(event.id) >= event.capacity) throw AppException("Sorry, the event sold out before payment completed")

            val confirmed = booking.copy(
                status = BookingStatus.CONFIRMED,
                ticketCode = newUniqueTicketCode(),
                paymentMethod = network.label,
                paymentRef = "SIM-" + System.currentTimeMillis().toString(36).uppercase(),
                payerPhone = local,
                confirmedAt = System.currentTimeMillis(),
            )
            bookings.update(confirmed)
            confirmed
        }
    }

    suspend fun cancelBooking(bookingId: Long, userId: Long): Result<Unit> = runCatching {
        db.withTransaction {
            val booking = bookings.findById(bookingId) ?: throw AppException("Booking not found")
            if (booking.userId != userId) throw AppException("This is not your booking")
            if (booking.isUsed) throw AppException("A ticket that has been used cannot be cancelled")
            bookings.update(booking.copy(status = BookingStatus.CANCELLED, ticketCode = null))
        }
    }

    /** Verifies a ticket at the entrance of [eventId] and marks it used if it is valid. */
    suspend fun verifyTicket(rawInput: String, eventId: Long, organizerId: Long): VerificationResult {
        val code = TicketCodes.parse(rawInput) ?: return VerificationResult.Unreadable
        return db.withTransaction {
            val booking = bookings.findByTicketCode(code) ?: return@withTransaction VerificationResult.NotFound
            val event = events.findById(booking.eventId) ?: return@withTransaction VerificationResult.NotFound
            val holder = users.findById(booking.userId)?.fullName ?: "Unknown attendee"
            when (val decision = TicketVerification.decide(booking, event, holder, eventId, organizerId)) {
                is TicketVerification.Decision.Reject -> decision.result
                TicketVerification.Decision.Admit -> {
                    val changed = bookings.markCheckedIn(booking.id, System.currentTimeMillis(), organizerId)
                    if (changed == 1) {
                        VerificationResult.Admitted(holder, event.title, code)
                    } else {
                        val latest = bookings.findById(booking.id)
                        VerificationResult.AlreadyUsed(holder, latest?.checkedInAt ?: System.currentTimeMillis(), code)
                    }
                }
            }
        }
    }

    private suspend fun newUniqueTicketCode(): String {
        while (true) {
            val code = TicketCodes.generate()
            if (bookings.findByTicketCode(code) == null) return code
        }
    }

    companion object {
        /** Shared with authorised organizers by the events office; restricts who can manage events. */
        const val ORGANIZER_ACCESS_CODE = "MUBS-ORG-2026"
    }
}
