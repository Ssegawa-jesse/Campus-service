package ug.ac.mubs.eventpass.data

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.Query
import androidx.room.Transaction
import androidx.room.Update
import kotlinx.coroutines.flow.Flow

@Dao
interface UserDao {
    @Insert
    suspend fun insert(user: User): Long

    @Query("SELECT * FROM users WHERE email = :email COLLATE NOCASE LIMIT 1")
    suspend fun findByEmail(email: String): User?

    @Query("SELECT * FROM users WHERE id = :id")
    suspend fun findById(id: Long): User?

    @Query("SELECT * FROM users WHERE id = :id")
    fun observeById(id: Long): Flow<User?>

    @Query("SELECT COUNT(*) FROM users")
    suspend fun count(): Int
}

private const val EVENT_WITH_STATS = """
    SELECT e.*,
        (SELECT COUNT(*) FROM bookings b WHERE b.eventId = e.id AND b.status = 'CONFIRMED') AS booked,
        (SELECT COUNT(*) FROM bookings b WHERE b.eventId = e.id AND b.status = 'CONFIRMED'
            AND b.checkedInAt IS NOT NULL) AS checkedIn
    FROM events e
"""

@Dao
interface EventDao {
    @Insert
    suspend fun insert(event: Event): Long

    @Update
    suspend fun update(event: Event)

    @Query("SELECT * FROM events WHERE id = :id")
    suspend fun findById(id: Long): Event?

    @Query("$EVENT_WITH_STATS WHERE e.endTime >= :now AND e.isCancelled = 0 ORDER BY e.startTime")
    fun observeUpcoming(now: Long): Flow<List<EventWithStats>>

    @Query("$EVENT_WITH_STATS WHERE e.id = :id")
    fun observeById(id: Long): Flow<EventWithStats?>

    @Query("$EVENT_WITH_STATS WHERE e.organizerId = :organizerId ORDER BY e.startTime DESC")
    fun observeByOrganizer(organizerId: Long): Flow<List<EventWithStats>>
}

@Dao
interface BookingDao {
    @Insert
    suspend fun insert(booking: Booking): Long

    @Update
    suspend fun update(booking: Booking)

    @Query("SELECT * FROM bookings WHERE id = :id")
    suspend fun findById(id: Long): Booking?

    @Query("SELECT * FROM bookings WHERE eventId = :eventId AND userId = :userId")
    suspend fun findForUserAndEvent(userId: Long, eventId: Long): Booking?

    @Query("SELECT * FROM bookings WHERE eventId = :eventId AND userId = :userId")
    fun observeForUserAndEvent(userId: Long, eventId: Long): Flow<Booking?>

    @Query("SELECT * FROM bookings WHERE ticketCode = :code")
    suspend fun findByTicketCode(code: String): Booking?

    @Query("SELECT COUNT(*) FROM bookings WHERE eventId = :eventId AND status = 'CONFIRMED'")
    suspend fun countConfirmed(eventId: Long): Int

    @Transaction
    @Query("SELECT * FROM bookings WHERE userId = :userId AND status != 'CANCELLED' ORDER BY bookedAt DESC")
    fun observeForUser(userId: Long): Flow<List<BookingWithEvent>>

    @Transaction
    @Query("SELECT * FROM bookings WHERE id = :id")
    fun observeWithEvent(id: Long): Flow<BookingWithEvent?>

    @Transaction
    @Query("SELECT * FROM bookings WHERE eventId = :eventId AND status != 'CANCELLED' ORDER BY bookedAt")
    fun observeAttendees(eventId: Long): Flow<List<Attendee>>

    /** Marks a ticket used only if nobody has used it yet; returns the number of rows changed. */
    @Query(
        """UPDATE bookings SET checkedInAt = :time, checkedInBy = :organizerId
           WHERE id = :id AND status = 'CONFIRMED' AND checkedInAt IS NULL"""
    )
    suspend fun markCheckedIn(id: Long, time: Long, organizerId: Long): Int
}
