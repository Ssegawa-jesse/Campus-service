package ug.ac.mubs.eventpass

import android.app.Application
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import ug.ac.mubs.eventpass.data.EventPassDatabase
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.data.SessionManager

/** Holds the app-wide singletons (simple manual dependency injection). */
class AppContainer(app: Application) {
    private val appScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    val database = EventPassDatabase.create(app)
    val repository = EventPassRepository(database, appScope)
    val session = SessionManager(app)
}

class EventPassApp : Application() {
    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)
    }
}
