package ug.ac.mubs.eventpass.ui.organizer

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Cancel
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material.icons.outlined.EventBusy
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.MenuAnchorType
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewModelScope
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.data.EventWithStats
import ug.ac.mubs.eventpass.data.VerificationResult
import ug.ac.mubs.eventpass.domain.Formatters
import ug.ac.mubs.eventpass.ui.common.CenteredLoading
import ug.ac.mubs.eventpass.ui.common.EmptyState
import ug.ac.mubs.eventpass.ui.common.appViewModel
import ug.ac.mubs.eventpass.ui.theme.ErrorRed
import ug.ac.mubs.eventpass.ui.theme.SuccessGreen
import ug.ac.mubs.eventpass.ui.theme.WarningAmber

class ScanViewModel(
    private val repository: EventPassRepository,
    private val organizerId: Long,
    initialEventId: Long?,
) : ViewModel() {
    /** Events still running or upcoming, the ones that can be checked into. */
    val events: StateFlow<List<EventWithStats>?> = repository.observeOrganizerEvents(organizerId)
        .map { list -> list.filter { !it.event.isCancelled && it.event.endTime >= System.currentTimeMillis() }.sortedBy { it.event.startTime } }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)

    var selectedEventId by mutableStateOf(initialEventId)
    var result by mutableStateOf<VerificationResult?>(null)
        private set
    var checking by mutableStateOf(false)
        private set

    fun verify(input: String) {
        val eventId = selectedEventId ?: return
        checking = true
        viewModelScope.launch {
            result = repository.verifyTicket(input, eventId, organizerId)
            checking = false
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ScanScreen(organizerId: Long, initialEventId: Long?) {
    val vm = appViewModel(key = "scan-$organizerId-$initialEventId") { ScanViewModel(it.repository, organizerId, initialEventId) }
    val events by vm.events.collectAsStateWithLifecycle()
    var manualCode by remember { mutableStateOf("") }

    val scanner = rememberLauncherForActivityResult(ScanContract()) { scan ->
        scan.contents?.let(vm::verify)
    }

    val list = events
    if (list == null) {
        CenteredLoading()
        return
    }
    if (list.isEmpty()) {
        EmptyState(Icons.Outlined.EventBusy, "Nothing to check in", "You have no upcoming events. Create one from the dashboard.")
        return
    }
    LaunchedEffect(list) {
        if (vm.selectedEventId == null || list.none { it.event.id == vm.selectedEventId }) {
            vm.selectedEventId = list.first().event.id
        }
    }
    val selected = list.firstOrNull { it.event.id == vm.selectedEventId }

    Column(
        Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .imePadding()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        var expanded by remember { mutableStateOf(false) }
        ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = it }) {
            OutlinedTextField(
                value = selected?.event?.title ?: "Choose event",
                onValueChange = {}, readOnly = true,
                label = { Text("Checking in for") },
                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded) },
                modifier = Modifier.menuAnchor(MenuAnchorType.PrimaryNotEditable).fillMaxWidth(),
            )
            ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                list.forEach { item ->
                    DropdownMenuItem(
                        text = { Text(item.event.title + " · " + Formatters.date(item.event.startTime)) },
                        onClick = { vm.selectedEventId = item.event.id; expanded = false },
                    )
                }
            }
        }
        selected?.let {
            Text(
                "${it.checkedIn} of ${it.booked} ticket holders checked in",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        Button(
            onClick = {
                scanner.launch(
                    ScanOptions()
                        .setDesiredBarcodeFormats(ScanOptions.QR_CODE)
                        .setPrompt("Point the camera at the student's ticket QR code")
                        .setBeepEnabled(true)
                        .setOrientationLocked(false),
                )
            },
            enabled = selected != null && !vm.checking,
            modifier = Modifier.fillMaxWidth().height(64.dp),
        ) {
            Icon(Icons.Filled.QrCodeScanner, contentDescription = null)
            Spacer(Modifier.size(8.dp))
            Text("Scan ticket QR code")
        }

        Text("Or enter the ticket code", style = MaterialTheme.typography.titleSmall)
        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            OutlinedTextField(
                value = manualCode,
                onValueChange = { manualCode = it.uppercase() },
                placeholder = { Text("EPM-XXXX-XXXX-XXXX") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Characters),
                modifier = Modifier.weight(1f),
            )
            OutlinedButton(onClick = { vm.verify(manualCode) }, enabled = manualCode.isNotBlank() && !vm.checking) {
                Text("Verify")
            }
        }

        vm.result?.let { ResultCard(it) }
    }
}

@Composable
private fun ResultCard(result: VerificationResult) {
    val (color, icon, title, body) = when (result) {
        is VerificationResult.Admitted -> Quad(SuccessGreen, Icons.Filled.CheckCircle, "VALID – admit", "${result.attendeeName}\n${result.eventTitle}\n${result.code}")
        is VerificationResult.AlreadyUsed -> Quad(
            ErrorRed, Icons.Filled.Cancel, "ALREADY USED",
            "${result.attendeeName}'s ticket ${result.code} was already scanned at ${Formatters.dateTime(result.usedAt)}. Do not admit.",
        )
        is VerificationResult.WrongEvent -> Quad(WarningAmber, Icons.Filled.Warning, "Wrong event", "This ticket is for \"${result.ticketEventTitle}\".")
        VerificationResult.NotConfirmed -> Quad(WarningAmber, Icons.Filled.Warning, "Not confirmed", "This booking has not been paid for or was cancelled.")
        VerificationResult.NotYourEvent -> Quad(ErrorRed, Icons.Filled.Cancel, "Not authorised", "This ticket belongs to an event managed by another organizer.")
        VerificationResult.NotFound -> Quad(ErrorRed, Icons.Filled.Cancel, "Invalid ticket", "No ticket with this code exists. It may be fake.")
        VerificationResult.Unreadable -> Quad(ErrorRed, Icons.Filled.Cancel, "Not an Event Pass ticket", "The scanned code is not a valid Event Pass MUBS ticket.")
    }
    Card(
        colors = CardDefaults.cardColors(containerColor = color.copy(alpha = 0.12f)),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(icon, contentDescription = null, tint = color, modifier = Modifier.size(48.dp))
            Column(Modifier.padding(start = 16.dp)) {
                Text(title, color = color, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                Text(body, style = MaterialTheme.typography.bodyMedium)
            }
        }
    }
}

private data class Quad(val color: Color, val icon: ImageVector, val title: String, val body: String)
