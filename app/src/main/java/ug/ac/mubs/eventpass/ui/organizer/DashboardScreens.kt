package ug.ac.mubs.eventpass.ui.organizer

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.automirrored.outlined.EventNote
import androidx.compose.material.icons.outlined.People
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExtendedFloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.ListItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import ug.ac.mubs.eventpass.data.Attendee
import ug.ac.mubs.eventpass.data.BookingStatus
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.data.EventWithStats
import ug.ac.mubs.eventpass.domain.Formatters
import ug.ac.mubs.eventpass.ui.common.CenteredLoading
import ug.ac.mubs.eventpass.ui.common.EmptyState
import ug.ac.mubs.eventpass.ui.common.EventCard
import ug.ac.mubs.eventpass.ui.common.StatusPill
import ug.ac.mubs.eventpass.ui.common.appViewModel
import ug.ac.mubs.eventpass.ui.theme.SuccessGreen
import ug.ac.mubs.eventpass.ui.theme.WarningAmber

// ---------------- Dashboard ----------------

class DashboardViewModel(repository: EventPassRepository, organizerId: Long) : ViewModel() {
    val events: StateFlow<List<EventWithStats>?> = repository.observeOrganizerEvents(organizerId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)
}

@Composable
fun DashboardScreen(
    organizerId: Long,
    onCreateEvent: () -> Unit,
    onOpenEvent: (Long) -> Unit,
) {
    val vm = appViewModel(key = "dashboard-$organizerId") { DashboardViewModel(it.repository, organizerId) }
    val events by vm.events.collectAsStateWithLifecycle()

    Scaffold(
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = onCreateEvent,
                icon = { Icon(Icons.Filled.Add, contentDescription = null) },
                text = { Text("New event") },
            )
        },
    ) { padding ->
        val list = events
        when {
            list == null -> CenteredLoading()
            list.isEmpty() -> EmptyState(
                Icons.AutoMirrored.Outlined.EventNote, "No events yet", "Create your first event to start taking bookings.",
                Modifier.padding(padding),
            )
            else -> LazyColumn(
                contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 16.dp, bottom = 96.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
                modifier = Modifier.padding(padding),
            ) {
                item { SummaryRow(list) }
                item { Text("Your events", style = MaterialTheme.typography.titleMedium, modifier = Modifier.padding(top = 8.dp)) }
                items(list, key = { it.event.id }) { item ->
                    EventCard(item, onClick = { onOpenEvent(item.event.id) }) {
                        Spacer(Modifier.height(8.dp))
                        val progress = if (item.booked == 0) 0f else item.checkedIn.toFloat() / item.booked
                        LinearProgressIndicator(progress = { progress }, modifier = Modifier.fillMaxWidth())
                        Text(
                            "${item.checkedIn} of ${item.booked} checked in" + if (item.event.isCancelled) " · CANCELLED" else "",
                            style = MaterialTheme.typography.labelSmall,
                            modifier = Modifier.padding(top = 4.dp),
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun SummaryRow(events: List<EventWithStats>) {
    val tickets = events.sumOf { it.booked }
    val checkedIn = events.sumOf { it.checkedIn }
    val revenue = events.sumOf { it.booked.toLong() * it.event.priceUgx }
    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        StatCard("Events", events.size.toString(), Modifier.weight(1f))
        StatCard("Tickets", tickets.toString(), Modifier.weight(1f))
        StatCard("Checked in", checkedIn.toString(), Modifier.weight(1f))
    }
    Spacer(Modifier.height(8.dp))
    StatCard("Ticket revenue", Formatters.price(revenue).replace("Free", "UGX 0"), Modifier.fillMaxWidth())
}

@Composable
fun StatCard(label: String, value: String, modifier: Modifier = Modifier) {
    Card(modifier, colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)) {
        Column(Modifier.padding(12.dp)) {
            Text(value, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(label, style = MaterialTheme.typography.labelMedium)
        }
    }
}

// ---------------- Attendees of one event ----------------

class AttendeesViewModel(repository: EventPassRepository, eventId: Long) : ViewModel() {
    val event: StateFlow<EventWithStats?> = repository.observeEvent(eventId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)
    val attendees: StateFlow<List<Attendee>?> = repository.observeAttendees(eventId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AttendeesScreen(
    eventId: Long,
    onBack: () -> Unit,
    onEdit: () -> Unit,
    onScan: () -> Unit,
) {
    val vm = appViewModel(key = "attendees-$eventId") { AttendeesViewModel(it.repository, eventId) }
    val event by vm.event.collectAsStateWithLifecycle()
    val attendees by vm.attendees.collectAsStateWithLifecycle()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(event?.event?.title ?: "Event", maxLines = 1) },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
                actions = { IconButton(onClick = onEdit) { Icon(Icons.Filled.Edit, "Edit event") } },
            )
        },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = onScan,
                icon = { Icon(Icons.Filled.QrCodeScanner, contentDescription = null) },
                text = { Text("Check in") },
            )
        },
    ) { padding ->
        val stats = event
        val list = attendees
        if (stats == null || list == null) {
            CenteredLoading()
            return@Scaffold
        }
        LazyColumn(
            Modifier.padding(padding).fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 8.dp, bottom = 96.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            item {
                Text(
                    Formatters.dateTime(stats.event.startTime) + " · " + stats.event.venue,
                    style = MaterialTheme.typography.bodySmall,
                )
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    StatCard("Tickets", "${stats.booked}/${stats.event.capacity}", Modifier.weight(1f))
                    StatCard("Checked in", stats.checkedIn.toString(), Modifier.weight(1f))
                    StatCard("Pending pay", list.count { it.booking.status == BookingStatus.PENDING_PAYMENT }.toString(), Modifier.weight(1f))
                }
                Text("Attendees", style = MaterialTheme.typography.titleMedium, modifier = Modifier.padding(top = 16.dp))
            }
            if (list.isEmpty()) {
                item {
                    Row(Modifier.padding(vertical = 24.dp), verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Outlined.People, null, Modifier.size(24.dp))
                        Text("No bookings yet", Modifier.padding(start = 8.dp))
                    }
                }
            }
            items(list, key = { it.booking.id }) { a ->
                Card {
                    ListItem(
                        headlineContent = { Text(a.user.fullName) },
                        supportingContent = {
                            Text(
                                listOfNotNull(a.user.studentNumber, a.booking.ticketCode).joinToString(" · "),
                                fontFamily = FontFamily.Monospace,
                            )
                        },
                        trailingContent = {
                            when {
                                a.booking.status == BookingStatus.PENDING_PAYMENT -> StatusPill("Unpaid", WarningAmber)
                                a.booking.isUsed -> StatusPill("In " + Formatters.time(a.booking.checkedInAt!!), SuccessGreen)
                                else -> StatusPill("Booked", MaterialTheme.colorScheme.primary)
                            }
                        },
                    )
                }
            }
        }
    }
}
