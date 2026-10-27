package ug.ac.mubs.eventpass.data

import androidx.room.withTransaction
import ug.ac.mubs.eventpass.domain.PasswordHasher
import java.time.LocalDate
import java.time.LocalTime
import java.time.ZoneId

/** Fills an empty database with demo accounts and upcoming events so the prototype can be explored immediately. */
object DemoDataSeeder {
    const val DEMO_ORGANIZER_EMAIL = "organizer@mubs.ac.ug"
    const val DEMO_ORGANIZER_PASSWORD = "organizer123"
    const val DEMO_STUDENT_EMAIL = "student@mubs.ac.ug"
    const val DEMO_STUDENT_PASSWORD = "student123"

    suspend fun seedIfEmpty(db: EventPassDatabase) {
        if (db.userDao().count() > 0) return
        db.withTransaction {
            val organizerId = db.userDao().insert(user("MUBS Events Office", DEMO_ORGANIZER_EMAIL, null, DEMO_ORGANIZER_PASSWORD, Role.ORGANIZER))
            db.userDao().insert(user("Demo Student", DEMO_STUDENT_EMAIL, "2400700001", DEMO_STUDENT_PASSWORD, Role.STUDENT))

            demoEvents(organizerId).forEach { db.eventDao().insert(it) }
        }
    }

    private fun user(name: String, email: String, studentNo: String?, password: String, role: Role): User {
        val salt = PasswordHasher.newSalt()
        return User(
            fullName = name,
            email = email,
            studentNumber = studentNo,
            passwordHash = PasswordHasher.hash(password, salt),
            salt = salt,
            role = role,
        )
    }

    private fun at(daysFromNow: Long, hour: Int, minute: Int = 0): Long =
        LocalDate.now().plusDays(daysFromNow).atTime(LocalTime.of(hour, minute))
            .atZone(ZoneId.systemDefault()).toInstant().toEpochMilli()

    private fun demoEvents(organizerId: Long) = listOf(
        Event(
            title = "MUBS Career Fair 2026",
            description = "Meet recruiters from banks, telecoms, audit firms and NGOs. Bring copies of your CV, " +
                "attend CV clinics and on-the-spot interviews for internships and graduate trainee programmes.",
            category = EventCategory.CAREER,
            venue = "MUBS Main Hall, Nakawa",
            startTime = at(5, 9), endTime = at(5, 16),
            priceUgx = 0, capacity = 300, organizerId = organizerId,
        ),
        Event(
            title = "East African Business Conference",
            description = "A one-day conference on entrepreneurship, digital finance and regional trade with " +
                "keynote speakers from industry and academia. Lunch and certificate of attendance included.",
            category = EventCategory.CONFERENCE,
            venue = "MUBS Auditorium",
            startTime = at(12, 8, 30), endTime = at(12, 17),
            priceUgx = 20_000, capacity = 250, organizerId = organizerId,
        ),
        Event(
            title = "Freshers' Night 2026",
            description = "Welcome party for first-year students with live music, DJs, games and food. " +
                "Show your student ID together with your Event Pass ticket at the gate.",
            category = EventCategory.SOCIAL,
            venue = "MUBS Sports Grounds",
            startTime = at(8, 18), endTime = at(8, 23, 30),
            priceUgx = 10_000, capacity = 800, organizerId = organizerId,
        ),
        Event(
            title = "Excel & Power BI for Business Workshop",
            description = "Hands-on workshop covering pivot tables, dashboards and data storytelling. " +
                "Bring a laptop with Excel installed. Limited seats.",
            category = EventCategory.WORKSHOP,
            venue = "ICT Lab 3, Faculty of Computing & Informatics",
            startTime = at(3, 14), endTime = at(3, 17),
            priceUgx = 5_000, capacity = 40, organizerId = organizerId,
        ),
        Event(
            title = "Research Methods Seminar",
            description = "Graduate school seminar on choosing a research design, sampling and writing a strong proposal.",
            category = EventCategory.SEMINAR,
            venue = "Graduate Research Centre, Room 2",
            startTime = at(2, 10), endTime = at(2, 13),
            priceUgx = 0, capacity = 80, organizerId = organizerId,
        ),
        Event(
            title = "Accounting Students Association Dinner",
            description = "Annual dinner and awards evening of the MUBS Accounting Students Association (MASA).",
            category = EventCategory.ASSOCIATION,
            venue = "Faculty of Commerce Pavilion",
            startTime = at(20, 19), endTime = at(20, 23),
            priceUgx = 35_000, capacity = 150, organizerId = organizerId,
        ),
    )
}
