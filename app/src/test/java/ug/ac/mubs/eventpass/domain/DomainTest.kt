package ug.ac.mubs.eventpass.domain

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class PasswordHasherTest {
    @Test
    fun `correct password verifies and wrong one does not`() {
        val salt = PasswordHasher.newSalt()
        val hash = PasswordHasher.hash("secret123", salt)
        assertTrue(PasswordHasher.verify("secret123", salt, hash))
        assertFalse(PasswordHasher.verify("Secret123", salt, hash))
    }

    @Test
    fun `same password with different salts gives different hashes`() {
        assertNotEquals(
            PasswordHasher.hash("secret123", PasswordHasher.newSalt()),
            PasswordHasher.hash("secret123", PasswordHasher.newSalt()),
        )
    }
}

class TicketCodesTest {
    @Test
    fun `generated codes parse back to themselves`() {
        repeat(200) {
            val code = TicketCodes.generate()
            assertEquals(code, TicketCodes.parse(code))
            assertEquals(code, TicketCodes.parse(TicketCodes.toQrPayload(code)))
        }
    }

    @Test
    fun `hand typed codes are normalised`() {
        assertEquals("EPM-7KQ2-X9HD-4MWT", TicketCodes.parse(" epm-7kq2-x9hd-4mwt "))
        assertEquals("EPM-7KQ2-X9HD-4MWT", TicketCodes.parse("EPM7KQ2X9HD4MWT"))
    }

    @Test
    fun `foreign or malformed codes are rejected`() {
        assertNull(TicketCodes.parse("https://example.com"))
        assertNull(TicketCodes.parse("EPM-0000-1111-OOOO")) // ambiguous characters are never issued
        assertNull(TicketCodes.parse("EPM-ABCD-EFGH"))
        assertNull(TicketCodes.parse(""))
    }
}

class ValidationTest {
    @Test
    fun `ugandan numbers are normalised`() {
        assertEquals("0772123456", Validation.normaliseUgandanPhone("0772 123 456"))
        assertEquals("0772123456", Validation.normaliseUgandanPhone("+256772123456"))
        assertEquals("0701123456", Validation.normaliseUgandanPhone("256701123456"))
        assertNull(Validation.normaliseUgandanPhone("0412123456")) // landline
        assertNull(Validation.normaliseUgandanPhone("07721234"))
    }

    @Test
    fun `network is detected from prefix`() {
        assertEquals(MobileNetwork.MTN, Validation.networkFor("0772123456"))
        assertEquals(MobileNetwork.MTN, Validation.networkFor("0761123456"))
        assertEquals(MobileNetwork.AIRTEL, Validation.networkFor("0701123456"))
        assertEquals(MobileNetwork.AIRTEL, Validation.networkFor("+256751123456"))
        assertNull(Validation.networkFor("0791123456"))
    }

    @Test
    fun `email validation`() {
        assertTrue(Validation.isValidEmail("2400725284@mubs.ac.ug"))
        assertFalse(Validation.isValidEmail("student@"))
    }
}
