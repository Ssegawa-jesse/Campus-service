package ug.ac.mubs.eventpass.domain

object Validation {
    private val emailPattern = Regex("^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$")

    fun isValidEmail(email: String): Boolean = emailPattern.matches(email.trim())

    /** Ugandan mobile numbers: 07XXXXXXXX, 2567XXXXXXXX or +2567XXXXXXXX. Returns 07XXXXXXXX or null. */
    fun normaliseUgandanPhone(input: String): String? {
        val digits = input.filter { it.isDigit() }
        val local = when {
            digits.startsWith("256") && digits.length == 12 -> "0" + digits.substring(3)
            digits.startsWith("0") && digits.length == 10 -> digits
            else -> return null
        }
        return local.takeIf { it[1] == '7' }
    }

    /** Detects the mobile money network from the number prefix. */
    fun networkFor(phone: String): MobileNetwork? {
        val local = normaliseUgandanPhone(phone) ?: return null
        return when (local.substring(0, 3)) {
            "077", "078", "076" -> MobileNetwork.MTN
            "070", "075", "074" -> MobileNetwork.AIRTEL
            else -> null
        }
    }

    fun passwordProblem(password: String): String? = when {
        password.length < 6 -> "Password must be at least 6 characters"
        else -> null
    }
}

enum class MobileNetwork(val label: String) {
    MTN("MTN Mobile Money"),
    AIRTEL("Airtel Money"),
}
