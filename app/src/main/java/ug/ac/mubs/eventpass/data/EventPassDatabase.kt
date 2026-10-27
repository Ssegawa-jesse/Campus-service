package ug.ac.mubs.eventpass.data

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase

@Database(entities = [User::class, Event::class, Booking::class], version = 1, exportSchema = true)
abstract class EventPassDatabase : RoomDatabase() {
    abstract fun userDao(): UserDao
    abstract fun eventDao(): EventDao
    abstract fun bookingDao(): BookingDao

    companion object {
        fun create(context: Context): EventPassDatabase =
            Room.databaseBuilder(context, EventPassDatabase::class.java, "eventpass.db").build()
    }
}
