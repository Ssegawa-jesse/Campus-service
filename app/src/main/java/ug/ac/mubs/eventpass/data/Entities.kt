package ug.ac.mubs.eventpass.data

import androidx.room.Embedded
import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey
import androidx.room.Relation

enum class Role { STUDENT, ORGANIZER }

enum class EventCategory(val label: String) {
    CONFERENCE("Conference"),
    CAREER("Career Fair"),
    WORKSHOP("Workshop"),
    SEMINAR("Seminar"),
    SOCIAL("Social & Parties"),
    ASSOCIATION("Associations"),
    SPORTS("Sports"),
}

enum class BookingStatus { PENDING_PAYMENT, CONFIRMED, CANCELLED }

@Entity(tableName = "users", indices = [Index(value = ["email"], unique = true)])
data class User(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val fullName: String,
    val email: String,
    val studentNumber: String?,
    val passwordHash: String,
    val salt: String,
    val role: Role,
    val createdAt: Long = System.currentTimeMillis(),
)

@Entity(
    tableName = "events",
    foreignKeys = [ForeignKey(entity = User::class, parentColumns = ["id"], childColumns = ["organizerId"])],
    indices = [Index("organizerId"), Index("startTime")],
)
data class Event(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val title: String,
    val description: String,
    val category: EventCategory,
    val venue: String,
    val startTime: Long,
    val endTime: Long,
    val priceUgx: Long,
    val capacity: Int,
    val organizerId: Long,
    val isCancelled: Boolean = false,
    val createdAt: Long = System.currentTimeMillis(),
) {
    val isFree: Boolean get() = priceUgx <= 0
}

@Entity(
    tableName = "bookings",
    foreignKeys = [
        ForeignKey(entity = Event::class, parentColumns = ["id"], childColumns = ["eventId"], onDelete = ForeignKey.CASCADE),
        ForeignKey(entity = User::class, parentColumns = ["id"], childColumns = ["userId"], onDelete = ForeignKey.CASCADE),
    ],
    indices = [
        // A student holds at most one booking per event.
        Index(value = ["eventId", "userId"], unique = true),
        Index("userId"),
        Index(value = ["ticketCode"], unique = true),
    ],
)
data class Booking(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val eventId: Long,
    val userId: Long,
    val status: BookingStatus,
    val amountUgx: Long,
    val ticketCode: String? = null,
    val paymentMethod: String? = null,
    val paymentRef: String? = null,
    val payerPhone: String? = null,
    val bookedAt: Long = System.currentTimeMillis(),
    val confirmedAt: Long? = null,
    val checkedInAt: Long? = null,
    val checkedInBy: Long? = null,
) {
    val isUsed: Boolean get() = checkedInAt != null
}

/** An event together with live booking counts. */
data class EventWithStats(
    @Embedded val event: Event,
    val booked: Int,
    val checkedIn: Int,
) {
    val seatsLeft: Int get() = (event.capacity - booked).coerceAtLeast(0)
    val isSoldOut: Boolean get() = seatsLeft == 0
}

data class BookingWithEvent(
    @Embedded val booking: Booking,
    @Relation(parentColumn = "eventId", entityColumn = "id") val event: Event,
)

data class Attendee(
    @Embedded val booking: Booking,
    @Relation(parentColumn = "userId", entityColumn = "id") val user: User,
)
