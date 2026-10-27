package ug.ac.mubs.eventpass.domain

import java.security.SecureRandom

/**
 * Ticket identifiers look like `EPM-7KQ2-X9HD-4MWT`. The QR code encodes
 * `EVENTPASS:<code>` so a scanner can tell our tickets apart from other QR codes.
 */
object TicketCodes {
    const val QR_PREFIX = "EVENTPASS:"
    private const val PREFIX = "EPM"
    // No 0/O or 1/I to avoid confusion when a code is typed in by hand.
    private const val ALPHABET = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ"
    private val random = SecureRandom()
    private val codePattern = Regex("^EPM-[$ALPHABET]{4}-[$ALPHABET]{4}-[$ALPHABET]{4}$")

    fun generate(): String {
        val groups = List(3) { (1..4).map { ALPHABET[random.nextInt(ALPHABET.length)] }.joinToString("") }
        return (listOf(PREFIX) + groups).joinToString("-")
    }

    fun toQrPayload(code: String): String = QR_PREFIX + code

    /** Accepts a scanned QR payload or a hand-typed code and returns the normalised code, or null. */
    fun parse(input: String): String? {
        var text = input.trim().uppercase()
        if (text.startsWith(QR_PREFIX)) text = text.removePrefix(QR_PREFIX)
        text = text.replace(" ", "")
        if (!text.contains("-") && text.length == 15 && text.startsWith(PREFIX)) {
            text = listOf(text.substring(0, 3), text.substring(3, 7), text.substring(7, 11), text.substring(11, 15))
                .joinToString("-")
        }
        return text.takeIf { codePattern.matches(it) }
    }
}
