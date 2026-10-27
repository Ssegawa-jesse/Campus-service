package ug.ac.mubs.eventpass.data

import android.content.Context
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/** Remembers which user is logged in across app restarts. */
class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences("session", Context.MODE_PRIVATE)
    private val _userId = MutableStateFlow(prefs.getLong(KEY_USER_ID, NO_USER).takeIf { it != NO_USER })
    val userId: StateFlow<Long?> = _userId.asStateFlow()

    fun login(userId: Long) {
        prefs.edit().putLong(KEY_USER_ID, userId).apply()
        _userId.value = userId
    }

    fun logout() {
        prefs.edit().remove(KEY_USER_ID).apply()
        _userId.value = null
    }

    private companion object {
        const val KEY_USER_ID = "user_id"
        const val NO_USER = -1L
    }
}
