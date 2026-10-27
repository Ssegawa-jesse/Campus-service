package ug.ac.mubs.eventpass.data

/** Outcome of an organizer scanning or typing in a ticket at the entrance. */
sealed interface VerificationResult {
    data class Admitted(val attendeeName: String, val eventTitle: String, val code: String) : VerificationResult
    data class AlreadyUsed(val attendeeName: String, val usedAt: Long, val code: String) : VerificationResult
    data class WrongEvent(val ticketEventTitle: String) : VerificationResult
    data object NotConfirmed : VerificationResult
    data object NotYourEvent : VerificationResult
    data object NotFound : VerificationResult
    data object Unreadable : VerificationResult
}

/** Checks that can be decided before the ticket is marked used. */
internal object TicketVerification {
    sealed interface Decision {
        data object Admit : Decision
        data class Reject(val result: VerificationResult) : Decision
    }

    fun decide(
        booking: Booking,
        ticketEvent: Event,
        holderName: String,
        scanningEventId: Long,
        organizerId: Long,
    ): Decision = when {
        ticketEvent.organizerId != organizerId -> Decision.Reject(VerificationResult.NotYourEvent)
        booking.eventId != scanningEventId -> Decision.Reject(VerificationResult.WrongEvent(ticketEvent.title))
        booking.status != BookingStatus.CONFIRMED -> Decision.Reject(VerificationResult.NotConfirmed)
        booking.checkedInAt != null ->
            Decision.Reject(VerificationResult.AlreadyUsed(holderName, booking.checkedInAt, booking.ticketCode.orEmpty()))
        else -> Decision.Admit
    }
}
