package ug.ac.mubs.eventpass.data

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class TicketVerificationTest {
    private val organizerId = 1L
    private val event = Event(
        id = 10, title = "Career Fair", description = "", category = EventCategory.CAREER, venue = "Main Hall",
        startTime = 0, endTime = 1, priceUgx = 0, capacity = 10, organizerId = organizerId,
    )
    private val confirmed = Booking(
        id = 5, eventId = 10, userId = 2, status = BookingStatus.CONFIRMED, amountUgx = 0, ticketCode = "EPM-AAAA-BBBB-CCCC",
    )

    private fun decide(booking: Booking = confirmed, scanningEventId: Long = 10, organizer: Long = organizerId) =
        TicketVerification.decide(booking, event, "Jane", scanningEventId, organizer)

    @Test
    fun `valid unused ticket is admitted`() {
        assertEquals(TicketVerification.Decision.Admit, decide())
    }

    @Test
    fun `used ticket is rejected as duplicate entry`() {
        val result = decide(confirmed.copy(checkedInAt = 1234L)) as TicketVerification.Decision.Reject
        assertTrue(result.result is VerificationResult.AlreadyUsed)
        assertEquals(1234L, (result.result as VerificationResult.AlreadyUsed).usedAt)
    }

    @Test
    fun `unpaid or cancelled bookings are rejected`() {
        listOf(BookingStatus.PENDING_PAYMENT, BookingStatus.CANCELLED).forEach { status ->
            val result = decide(confirmed.copy(status = status)) as TicketVerification.Decision.Reject
            assertEquals(VerificationResult.NotConfirmed, result.result)
        }
    }

    @Test
    fun `ticket for another event is rejected`() {
        val result = decide(scanningEventId = 99) as TicketVerification.Decision.Reject
        assertEquals(VerificationResult.WrongEvent("Career Fair"), result.result)
    }

    @Test
    fun `organizer cannot check in tickets for someone else's event`() {
        val result = decide(organizer = 42) as TicketVerification.Decision.Reject
        assertEquals(VerificationResult.NotYourEvent, result.result)
    }
}
