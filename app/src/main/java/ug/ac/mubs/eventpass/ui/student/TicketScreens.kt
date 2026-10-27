package ug.ac.mubs.eventpass.ui.student

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.outlined.ConfirmationNumber
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import ug.ac.mubs.eventpass.data.BookingStatus
import ug.ac.mubs.eventpass.data.BookingWithEvent
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.domain.Formatters
import ug.ac.mubs.eventpass.domain.TicketCodes
import ug.ac.mubs.eventpass.domain.Validation
import ug.ac.mubs.eventpass.ui.common.CenteredLoading
import ug.ac.mubs.eventpass.ui.common.DateBadge
import ug.ac.mubs.eventpass.ui.common.EmptyState
import ug.ac.mubs.eventpass.ui.common.ErrorText
import ug.ac.mubs.eventpass.ui.common.LoadingButton
import ug.ac.mubs.eventpass.ui.common.QrCode
import ug.ac.mubs.eventpass.ui.common.StatusPill
import ug.ac.mubs.eventpass.ui.common.appViewModel
import ug.ac.mubs.eventpass.ui.theme.SuccessGreen
import ug.ac.mubs.eventpass.ui.theme.WarningAmber

// ---------------- Simulated payment ----------------

class PaymentViewModel(private val repository: EventPassRepository, private val bookingId: Long) : ViewModel() {
    val booking: StateFlow<BookingWithEvent?> = repository.observeBooking(bookingId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)

    var processing by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set

    fun pay(phone: String, pin: String, onPaid: () -> Unit) {
        if (processing) return
        processing = true
        error = null
        viewModelScope.launch {
            repository.payForBooking(bookingId, phone, pin)
                .onSuccess { onPaid() }
                .onFailure { error = it.message }
            processing = false
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaymentScreen(bookingId: Long, onBack: () -> Unit, onPaid: (Long) -> Unit) {
    val vm = appViewModel(key = "pay-$bookingId") { PaymentViewModel(it.repository, bookingId) }
    val data by vm.booking.collectAsStateWithLifecycle()
    var phone by rememberSaveable { mutableStateOf("") }
    var pin by rememberSaveable { mutableStateOf("") }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Mobile money payment") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        val current = data
        if (current == null) {
            CenteredLoading()
            return@Scaffold
        }
        Column(
            Modifier
                .padding(padding)
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .imePadding()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp)) {
                    Text(current.event.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    Text(Formatters.dateTime(current.event.startTime), style = MaterialTheme.typography.bodySmall)
                    HorizontalDivider(Modifier.padding(vertical = 12.dp))
                    Row {
                        Text("Amount due", Modifier.weight(1f))
                        Text(Formatters.price(current.booking.amountUgx), fontWeight = FontWeight.Bold)
                    }
                }
            }
            Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer)) {
                Text(
                    "Prototype mode: payments are simulated and no real money moves. Use any MTN (077/078/076) " +
                        "or Airtel (070/075/074) number and any 4-digit PIN. PIN 0000 simulates a declined payment.",
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(12.dp),
                )
            }
            OutlinedTextField(
                phone, { phone = it },
                label = { Text("Mobile money number") },
                placeholder = { Text("07XXXXXXXX") },
                supportingText = { Validation.networkFor(phone)?.let { Text(it.label) } },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                modifier = Modifier.fillMaxWidth(),
            )
            OutlinedTextField(
                pin, { if (it.length <= 5) pin = it.filter(Char::isDigit) },
                label = { Text("PIN") },
                singleLine = true,
                visualTransformation = PasswordVisualTransformation(),
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
                modifier = Modifier.fillMaxWidth(),
            )
            ErrorText(vm.error)
            if (vm.processing) {
                Text("Waiting for payment confirmation…", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            LoadingButton(
                "Pay ${Formatters.price(current.booking.amountUgx)}",
                loading = vm.processing,
                onClick = { vm.pay(phone, pin) { onPaid(bookingId) } },
            )
        }
    }
}

// ---------------- My tickets ----------------

class TicketsViewModel(repository: EventPassRepository, userId: Long) : ViewModel() {
    val bookings: StateFlow<List<BookingWithEvent>?> = repository.observeMyBookings(userId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)
}

@Composable
fun TicketsScreen(userId: Long, onOpenTicket: (BookingWithEvent) -> Unit) {
    val vm = appViewModel(key = "tickets-$userId") { TicketsViewModel(it.repository, userId) }
    val bookings by vm.bookings.collectAsStateWithLifecycle()
    val list = bookings
    when {
        list == null -> CenteredLoading()
        list.isEmpty() -> EmptyState(
            Icons.Outlined.ConfirmationNumber,
            "No tickets yet",
            "Browse events and book one – your digital tickets will appear here.",
        )
        else -> LazyColumn(contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            items(list, key = { it.booking.id }) { item ->
                Card(onClick = { onOpenTicket(item) }, modifier = Modifier.fillMaxWidth()) {
                    Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                        DateBadge(item.event.startTime)
                        Column(Modifier.weight(1f).padding(start = 16.dp)) {
                            Text(item.event.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                            Text(item.event.venue, style = MaterialTheme.typography.bodySmall)
                            Spacer(Modifier.height(6.dp))
                            BookingStatusPill(item)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun BookingStatusPill(item: BookingWithEvent) {
    val b = item.booking
    when {
        b.status == BookingStatus.PENDING_PAYMENT -> StatusPill("Awaiting payment", WarningAmber)
        b.isUsed -> StatusPill("Used " + Formatters.time(b.checkedInAt!!), MaterialTheme.colorScheme.outline)
        item.event.endTime < System.currentTimeMillis() -> StatusPill("Expired", MaterialTheme.colorScheme.outline)
        else -> StatusPill("Valid ticket", SuccessGreen)
    }
}

// ---------------- Ticket detail with QR ----------------

class TicketDetailViewModel(private val repository: EventPassRepository, private val bookingId: Long) : ViewModel() {
    val booking: StateFlow<BookingWithEvent?> = repository.observeBooking(bookingId)
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)
    var error by mutableStateOf<String?>(null)
        private set

    fun cancel(userId: Long, onDone: () -> Unit) {
        viewModelScope.launch {
            repository.cancelBooking(bookingId, userId).onSuccess { onDone() }.onFailure { error = it.message }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TicketDetailScreen(bookingId: Long, userId: Long, studentName: String, onBack: () -> Unit) {
    val vm = appViewModel(key = "ticket-$bookingId") { TicketDetailViewModel(it.repository, bookingId) }
    val data by vm.booking.collectAsStateWithLifecycle()
    var confirmCancel by rememberSaveable { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("My ticket") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        val current = data
        if (current == null) {
            CenteredLoading()
            return@Scaffold
        }
        val booking = current.booking
        val event = current.event
        Column(
            Modifier
                .padding(padding)
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Card(Modifier.fillMaxWidth()) {
                Column(
                    Modifier.padding(20.dp).fillMaxWidth(),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text(event.title, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, textAlign = TextAlign.Center)
                    Text(Formatters.dateTime(event.startTime), style = MaterialTheme.typography.bodyMedium)
                    Text(event.venue, style = MaterialTheme.typography.bodyMedium, textAlign = TextAlign.Center)
                    BookingStatusPill(current)
                    val code = booking.ticketCode
                    if (booking.status == BookingStatus.CONFIRMED && code != null) {
                        Spacer(Modifier.height(4.dp))
                        QrCode(TicketCodes.toQrPayload(code))
                        Text(code, fontFamily = FontFamily.Monospace, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        Text(
                            if (booking.isUsed) "This ticket was scanned at ${Formatters.dateTime(booking.checkedInAt!!)} and cannot be reused."
                            else "Show this QR code at the entrance. It can only be used once.",
                            style = MaterialTheme.typography.bodySmall,
                            textAlign = TextAlign.Center,
                        )
                    } else {
                        Text("Your ticket will appear here once payment is confirmed.", textAlign = TextAlign.Center)
                    }
                    HorizontalDivider(Modifier.padding(vertical = 8.dp))
                    DetailLine("Ticket holder", studentName)
                    DetailLine("Amount", Formatters.price(booking.amountUgx))
                    booking.paymentMethod?.let { DetailLine("Paid via", it) }
                    booking.paymentRef?.let { DetailLine("Payment ref", it) }
                    booking.confirmedAt?.let { DetailLine("Confirmed", Formatters.dateTime(it)) }
                }
            }
            ErrorText(vm.error)
            if (!booking.isUsed && event.endTime > System.currentTimeMillis()) {
                TextButton(onClick = { confirmCancel = true }) {
                    Text("Cancel booking", color = MaterialTheme.colorScheme.error)
                }
            }
        }
    }

    if (confirmCancel) {
        AlertDialog(
            onDismissRequest = { confirmCancel = false },
            title = { Text("Cancel this booking?") },
            text = { Text("Your ticket will stop working and the seat will be released. Refunds for paid events are handled by the organizer.") },
            confirmButton = {
                TextButton(onClick = { confirmCancel = false; vm.cancel(userId, onBack) }) { Text("Cancel booking") }
            },
            dismissButton = { TextButton(onClick = { confirmCancel = false }) { Text("Keep ticket") } },
        )
    }
}

@Composable
private fun DetailLine(label: String, value: String) {
    Row(Modifier.fillMaxWidth()) {
        Text(label, Modifier.weight(1f), color = MaterialTheme.colorScheme.onSurfaceVariant, style = MaterialTheme.typography.bodyMedium)
        Text(value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
    }
}
