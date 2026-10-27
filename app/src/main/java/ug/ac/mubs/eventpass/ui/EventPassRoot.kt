package ug.ac.mubs.eventpass.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.consumeWindowInsets
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.ConfirmationNumber
import androidx.compose.material.icons.filled.Dashboard
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import ug.ac.mubs.eventpass.data.BookingStatus
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.data.Role
import ug.ac.mubs.eventpass.data.SessionManager
import ug.ac.mubs.eventpass.data.User
import ug.ac.mubs.eventpass.ui.auth.AuthScreen
import ug.ac.mubs.eventpass.ui.common.CenteredLoading
import ug.ac.mubs.eventpass.ui.common.appViewModel
import ug.ac.mubs.eventpass.ui.organizer.AttendeesScreen
import ug.ac.mubs.eventpass.ui.organizer.DashboardScreen
import ug.ac.mubs.eventpass.ui.organizer.EventEditScreen
import ug.ac.mubs.eventpass.ui.organizer.ScanScreen
import ug.ac.mubs.eventpass.ui.student.EventDetailScreen
import ug.ac.mubs.eventpass.ui.student.EventsScreen
import ug.ac.mubs.eventpass.ui.student.PaymentScreen
import ug.ac.mubs.eventpass.ui.student.TicketDetailScreen
import ug.ac.mubs.eventpass.ui.student.TicketsScreen

sealed interface SessionState {
    data object Loading : SessionState
    data object LoggedOut : SessionState
    data class LoggedIn(val user: User) : SessionState
}

@OptIn(ExperimentalCoroutinesApi::class)
class RootViewModel(repository: EventPassRepository, private val session: SessionManager) : ViewModel() {
    val state: StateFlow<SessionState> = session.userId
        .flatMapLatest { id ->
            if (id == null) flowOf(SessionState.LoggedOut)
            else repository.observeUser(id).map { user -> user?.let { SessionState.LoggedIn(it) } ?: SessionState.LoggedOut }
        }
        .stateIn(viewModelScope, SharingStarted.Eagerly, SessionState.Loading)

    fun logout() = session.logout()
}

@Composable
fun EventPassRoot() {
    val vm = appViewModel { RootViewModel(it.repository, it.session) }
    val state by vm.state.collectAsStateWithLifecycle()
    when (val s = state) {
        SessionState.Loading -> CenteredLoading()
        SessionState.LoggedOut -> AuthScreen()
        is SessionState.LoggedIn -> when (s.user.role) {
            Role.STUDENT -> StudentApp(s.user, vm::logout)
            Role.ORGANIZER -> OrganizerApp(s.user, vm::logout)
        }
    }
}

private data class Tab(val route: String, val label: String, val icon: ImageVector)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun TabScaffold(nav: NavHostController, tabs: List<Tab>, content: @Composable (Modifier) -> Unit) {
    val backStack by nav.currentBackStackEntryAsState()
    val currentRoute = backStack?.destination?.route
    val currentTab = tabs.firstOrNull { currentRoute?.substringBefore("?") == it.route }

    Scaffold(
        topBar = {
            if (currentTab != null) TopAppBar(title = { Text(if (currentTab == tabs.first()) "Event Pass MUBS" else currentTab.label) })
        },
        bottomBar = {
            if (currentTab != null) {
                NavigationBar {
                    tabs.forEach { tab ->
                        NavigationBarItem(
                            selected = tab == currentTab,
                            onClick = {
                                nav.navigate(tab.route) {
                                    popUpTo(nav.graph.findStartDestination().id) { saveState = true }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            },
                            icon = { Icon(tab.icon, contentDescription = null) },
                            label = { Text(tab.label) },
                        )
                    }
                }
            }
        },
    ) { padding -> content(Modifier.padding(padding).consumeWindowInsets(padding)) }
}

@Composable
private fun StudentApp(user: User, onLogout: () -> Unit) {
    val nav = rememberNavController()
    val tabs = listOf(
        Tab("events", "Events", Icons.Filled.Event),
        Tab("tickets", "My Tickets", Icons.Filled.ConfirmationNumber),
        Tab("profile", "Profile", Icons.Filled.Person),
    )
    TabScaffold(nav, tabs) { modifier ->
        NavHost(nav, startDestination = "events", modifier = modifier) {
            composable("events") { EventsScreen(onOpenEvent = { nav.navigate("event/$it") }) }
            composable("tickets") {
                TicketsScreen(user.id, onOpenTicket = { item ->
                    if (item.booking.status == BookingStatus.PENDING_PAYMENT) nav.navigate("pay/${item.booking.id}")
                    else nav.navigate("ticket/${item.booking.id}")
                })
            }
            composable("profile") { ProfileScreen(user, onLogout) }
            composable("event/{id}", listOf(navArgument("id") { type = NavType.LongType })) { entry ->
                EventDetailScreen(
                    eventId = entry.arguments!!.getLong("id"),
                    userId = user.id,
                    onBack = { nav.popBackStack() },
                    onPay = { nav.navigate("pay/$it") },
                    onViewTicket = { nav.navigate("ticket/$it") },
                )
            }
            composable("pay/{id}", listOf(navArgument("id") { type = NavType.LongType })) { entry ->
                PaymentScreen(
                    bookingId = entry.arguments!!.getLong("id"),
                    onBack = { nav.popBackStack() },
                    onPaid = { id -> nav.navigate("ticket/$id") { popUpTo("pay/{id}") { inclusive = true } } },
                )
            }
            composable("ticket/{id}", listOf(navArgument("id") { type = NavType.LongType })) { entry ->
                TicketDetailScreen(
                    bookingId = entry.arguments!!.getLong("id"),
                    userId = user.id,
                    studentName = user.fullName,
                    onBack = { nav.popBackStack() },
                )
            }
        }
    }
}

@Composable
private fun OrganizerApp(user: User, onLogout: () -> Unit) {
    val nav = rememberNavController()
    val tabs = listOf(
        Tab("dashboard", "Dashboard", Icons.Filled.Dashboard),
        Tab("scan", "Check-in", Icons.Filled.QrCodeScanner),
        Tab("profile", "Profile", Icons.Filled.Person),
    )
    TabScaffold(nav, tabs) { modifier ->
        NavHost(nav, startDestination = "dashboard", modifier = modifier) {
            composable("dashboard") {
                DashboardScreen(
                    organizerId = user.id,
                    onCreateEvent = { nav.navigate("edit") },
                    onOpenEvent = { nav.navigate("attendees/$it") },
                )
            }
            composable(
                "scan?eventId={eventId}",
                listOf(navArgument("eventId") { type = NavType.LongType; defaultValue = -1L }),
            ) { entry ->
                ScanScreen(user.id, entry.arguments?.getLong("eventId")?.takeIf { it > 0 })
            }
            composable("profile") { ProfileScreen(user, onLogout) }
            composable(
                "edit?eventId={eventId}",
                listOf(navArgument("eventId") { type = NavType.LongType; defaultValue = -1L }),
            ) { entry ->
                EventEditScreen(
                    eventId = entry.arguments?.getLong("eventId")?.takeIf { it > 0 },
                    organizerId = user.id,
                    onDone = { nav.popBackStack() },
                )
            }
            composable("attendees/{id}", listOf(navArgument("id") { type = NavType.LongType })) { entry ->
                val eventId = entry.arguments!!.getLong("id")
                AttendeesScreen(
                    eventId = eventId,
                    onBack = { nav.popBackStack() },
                    onEdit = { nav.navigate("edit?eventId=$eventId") },
                    onScan = { nav.navigate("scan?eventId=$eventId") },
                )
            }
        }
    }
}

@Composable
private fun ProfileScreen(user: User, onLogout: () -> Unit) {
    Column(
        Modifier.fillMaxSize().padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Icon(Icons.Filled.AccountCircle, contentDescription = null, modifier = Modifier.size(96.dp), tint = MaterialTheme.colorScheme.primary)
        Text(user.fullName, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
        Text(user.email, style = MaterialTheme.typography.bodyMedium)
        user.studentNumber?.let { Text("Student no. $it", style = MaterialTheme.typography.bodyMedium) }
        Text(
            if (user.role == Role.ORGANIZER) "Authorised event organizer" else "Student",
            style = MaterialTheme.typography.labelLarge,
            color = MaterialTheme.colorScheme.secondary,
        )
        OutlinedButton(onClick = onLogout, modifier = Modifier.fillMaxWidth().padding(top = 24.dp)) {
            Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null)
            Text("  Log out")
        }
    }
}
