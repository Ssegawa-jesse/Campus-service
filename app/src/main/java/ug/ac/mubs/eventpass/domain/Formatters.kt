package ug.ac.mubs.eventpass.domain

import java.text.NumberFormat
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

object Formatters {
    private val dateTime = DateTimeFormatter.ofPattern("EEE d MMM yyyy, h:mm a", Locale.ENGLISH)
    private val date = DateTimeFormatter.ofPattern("EEE d MMM yyyy", Locale.ENGLISH)
    private val time = DateTimeFormatter.ofPattern("h:mm a", Locale.ENGLISH)

    private fun Long.zoned() = Instant.ofEpochMilli(this).atZone(ZoneId.systemDefault())

    fun dateTime(millis: Long): String = dateTime.format(millis.zoned())
    fun date(millis: Long): String = date.format(millis.zoned())
    fun time(millis: Long): String = time.format(millis.zoned())

    fun price(ugx: Long): String =
        if (ugx <= 0) "Free" else "UGX " + NumberFormat.getIntegerInstance(Locale.ENGLISH).format(ugx)
}
