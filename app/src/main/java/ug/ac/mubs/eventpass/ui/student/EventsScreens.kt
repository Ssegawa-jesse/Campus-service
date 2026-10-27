package ug.ac.mubs.eventpass.ui.student

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.outlined.CalendarMonth
import androidx.compose.material.icons.outlined.EventBusy
import androidx.compose.material.icons.outlined.Groups
import androidx.compose.material.icons.outlined.Payments
import androidx.compose.material.icons.outlined.Place
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import ug.ac.mubs.eventpass.data.Booking
import ug.ac.mubs.eventpass.data.BookingStatus
import ug.ac.mubs.eventpass.data.EventCategory
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.data.EventWithStats
import ug.ac.mubs.eventpass.domain.Formatters
import ug.ac.mubs.eventpass.ui.common.CategoryLabel
import ug.ac.mubs.eventpass.ui.common.CenteredLoading
import ug.ac.mubs.eventpass.ui.common.EmptyState
import ug.ac.mubs.eventpass.ui.common.ErrorText
import ug.ac.mubs.eventpass.ui.common.EventCard
import ug.ac.mubs.eventpass.ui.common.LoadingButton
import ug.ac.mubs.eventpass.ui.common.appViewModel

// ---------------- Browse events ----------------

class EventsViewModel(repository: EventPassRepository) : ViewModel() {
    val query = MutableStateFlow("")
    val category = MutableStateFlow<EventCategory?>(null)

    val events: StateFlow<List<EventWithStats>?> =
        combine(repository.observeUpcomingEvents(), query, category) { all, q, cat ->
            all.filter { item ->
                val e = item.event
                (cat == null || e.category == cat) &&
                    (q.isBlank() || listOf(e.title, e.description, e.venue, e.category.label).any { it.contains(q.trim(), ignoreCase = true) })
            }
        }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)
}

@Composable
fun EventsScreen(onOpenEvent: (Long) -> Unit) {
    val vm = appViewModel { EventsViewModel(it.repository) }
    val events by vm.events.collectAsStateWithLifecycle()
    val query by vm.query.collectAsStateWithLifecycle()
    val selected by vm.category.collectAsStateWithLifecycle()

    Column(Modifier.fillMaxSize()) {
        OutlinedTextField(
            value = query,
            onValueChange = { vm.query.value = it },
            leadingIcon = { Icon(Icons.Filled.Search, contentDescription = null) },
            placeholder = { Text("Search events, venues…") },
            singleLine = true,
            modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),
        )
        LazyRow(contentPadding = PaddingValues(horizontal = 16.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            item { FilterChip(selected = selected == null, onClick = { vm.category.value = null }, label = { Text("All") }) }
            items(EventCategory.entries) { cat ->
                FilterChip(
                    selected = selected == cat,
                    onClick = { vm.category.value = if (selected == cat) null else cat },
                    label = { Text(cat.label) },
                )
            }
        }
        val list = events
        when {
            list == null -> CenteredLoading()
            list.isEmpty() -> EmptyState(Icons.Outlined.EventBusy, "No events found", "Try another search or category.")
            else -> LazyColumn(
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                items(list, key = { it.event.id }) { item -> EventCard(item, onClick = { onOpenEvent(item.event.id) }) }
            }
        }
    }
}

// ---------------- Event details & booking ----------------

class EventDetailViewModel(
    private val repository: EventPassRepository,
    private val eventId: Long,
    private val userId: Long,
) : ViewModel() {
    val event: StateFlow<EventWithStats?> = repository.observeEvent(eventId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)
    val booking: StateFlow<Booking?> = repository.observeMyBookingFor(userId, eventId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)

    var loading by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set

    fun book(onBooked: (Booking) -> Unit) {
        if (loading) return
        loading = true
        error = null
        viewModelScope.launch {
            repository.bookEvent(userId, eventId)
                .onSuccess(onBooked)
                .onFailure { error = it.message }
            loading = false
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EventDetailScreen(
    eventId: Long,
    userId: Long,
    onBack: () -> Unit,
    onPay: (Long) -> Unit,
    onViewTicket: (Long) -> Unit,
) {
    val vm = appViewModel(key = "event-$eventId") { EventDetailViewModel(it.repository, eventId, userId) }
    val item by vm.event.collectAsStateWithLifecycle()
    val booking by vm.booking.collectAsStateWithLifecycle()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Event details") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        val current = item
        if (current == null) {
            CenteredLoading()
            return@Scaffold
        }
        val event = current.event
        Column(
            Modifier
                .padding(padding)
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            CategoryLabel(event.category)
            Text(event.title, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    InfoRow(Icons.Outlined.CalendarMonth, Formatters.dateTime(event.startTime), "Ends ${Formatters.time(event.endTime)}")
                    InfoRow(Icons.Outlined.Place, event.venue, null)
                    InfoRow(Icons.Outlined.Payments, Formatters.price(event.priceUgx), if (event.isFree) "No payment needed" else "Pay with MTN or Airtel Money")
                    InfoRow(
                        Icons.Outlined.Groups,
                        if (current.isSoldOut) "Sold out" else "${current.seatsLeft} of ${event.capacity} seats left",
                        "${current.booked} booked",
                    )
                }
            }
            Text("About this event", style = MaterialTheme.typography.titleMedium)
            Text(event.description, style = MaterialTheme.typography.bodyMedium)
            Spacer(Modifier.height(8.dp))

            val b = booking
            when {
                b != null && b.status == BookingStatus.CONFIRMED -> {
                    Text("You have a ticket for this event.", color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.SemiBold)
                    LoadingButton("View my ticket", loading = false, onClick = { onViewTicket(b.id) })
                }
                b != null && b.status == BookingStatus.PENDING_PAYMENT -> {
                    Text("Your booking is reserved and awaiting payment.", color = MaterialTheme.colorScheme.secondary)
                    LoadingButton("Complete payment", loading = false, onClick = { onPay(b.id) })
                }
                else -> {
                    ErrorText(vm.error)
                    LoadingButton(
                        text = when {
                            current.isSoldOut -> "Sold out"
                            event.isFree -> "Register (free)"
                            else -> "Book ticket – ${Formatters.price(event.priceUgx)}"
                        },
                        loading = vm.loading,
                        enabled = !current.isSoldOut,
                        onClick = {
                            vm.book { booked ->
                                if (booked.status == BookingStatus.CONFIRMED) onViewTicket(booked.id) else onPay(booked.id)
                            }
                        },
                    )
                }
            }
        }
    }
}

@Composable
private fun InfoRow(icon: ImageVector, title: String, subtitle: String?) {
    Row {
        Icon(icon, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
        Column(Modifier.padding(start = 12.dp)) {
            Text(title, style = MaterialTheme.typography.bodyLarge, fontWeight = FontWeight.Medium)
            if (subtitle != null) {
                Text(subtitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
    }
}
