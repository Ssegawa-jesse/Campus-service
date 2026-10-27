package ug.ac.mubs.eventpass.domain

import java.security.MessageDigest
import java.security.SecureRandom
import javax.crypto.SecretKeyFactory
import javax.crypto.spec.PBEKeySpec

/** Salted PBKDF2 password hashing so plain-text passwords are never stored. */
object PasswordHasher {
    private const val ITERATIONS = 120_000
    private const val KEY_LENGTH_BITS = 256
    private val random = SecureRandom()

    fun newSalt(): String {
        val bytes = ByteArray(16)
        random.nextBytes(bytes)
        return bytes.toHex()
    }

    fun hash(password: String, salt: String): String {
        val spec = PBEKeySpec(password.toCharArray(), salt.hexToBytes(), ITERATIONS, KEY_LENGTH_BITS)
        val factory = SecretKeyFactory.getInstance("PBKDF2WithHmacSHA256")
        return factory.generateSecret(spec).encoded.toHex()
    }

    fun verify(password: String, salt: String, expectedHash: String): Boolean =
        MessageDigest.isEqual(hash(password, salt).toByteArray(), expectedHash.toByteArray())

    private fun ByteArray.toHex(): String = joinToString("") { "%02x".format(it) }

    private fun String.hexToBytes(): ByteArray =
        chunked(2).map { it.toInt(16).toByte() }.toByteArray()
}
