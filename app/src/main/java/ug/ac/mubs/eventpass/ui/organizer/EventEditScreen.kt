package ug.ac.mubs.eventpass.ui.organizer

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MenuAnchorType
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TimePicker
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.material3.rememberTimePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch
import ug.ac.mubs.eventpass.data.Event
import ug.ac.mubs.eventpass.data.EventCategory
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.domain.Formatters
import ug.ac.mubs.eventpass.ui.common.CenteredLoading
import ug.ac.mubs.eventpass.ui.common.ErrorText
import ug.ac.mubs.eventpass.ui.common.LoadingButton
import ug.ac.mubs.eventpass.ui.common.appViewModel
import java.time.Instant
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime
import java.time.ZoneId
import java.time.ZoneOffset

class EventEditViewModel(
    private val repository: EventPassRepository,
    private val eventId: Long?,
    private val organizerId: Long,
) : ViewModel() {
    var loaded by mutableStateOf(eventId == null)
        private set
    var title by mutableStateOf("")
    var description by mutableStateOf("")
    var category by mutableStateOf(EventCategory.CONFERENCE)
    var venue by mutableStateOf("")
    var start by mutableStateOf(LocalDateTime.of(LocalDate.now().plusDays(7), LocalTime.of(9, 0)))
    var end by mutableStateOf(LocalDateTime.of(LocalDate.now().plusDays(7), LocalTime.of(17, 0)))
    var price by mutableStateOf("0")
    var capacity by mutableStateOf("100")
    var cancelled by mutableStateOf(false)
    var saving by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set
    private var original: Event? = null

    init {
        if (eventId != null) {
            viewModelScope.launch {
                repository.getEvent(eventId)?.let { e ->
                    original = e
                    title = e.title
                    description = e.description
                    category = e.category
                    venue = e.venue
                    start = e.startTime.toLocal()
                    end = e.endTime.toLocal()
                    price = e.priceUgx.toString()
                    capacity = e.capacity.toString()
                    cancelled = e.isCancelled
                }
                loaded = true
            }
        }
    }

    fun save(onSaved: () -> Unit) {
        val priceValue = price.filter(Char::isDigit).toLongOrNull()
        val capacityValue = capacity.filter(Char::isDigit).toIntOrNull()
        if (priceValue == null) { error = "Enter a ticket price (0 for a free event)"; return }
        if (capacityValue == null) { error = "Enter the number of seats"; return }
        val base = original ?: Event(
            title = "", description = "", category = category, venue = "",
            startTime = 0, endTime = 0, priceUgx = 0, capacity = 0, organizerId = organizerId,
        )
        val event = base.copy(
            title = title.trim(),
            description = description.trim(),
            category = category,
            venue = venue.trim(),
            startTime = start.toMillis(),
            endTime = end.toMillis(),
            priceUgx = priceValue,
            capacity = capacityValue,
            isCancelled = cancelled,
        )
        saving = true
        error = null
        viewModelScope.launch {
            repository.saveEvent(event, organizerId).onSuccess { onSaved() }.onFailure { error = it.message }
            saving = false
        }
    }

    private fun Long.toLocal(): LocalDateTime = LocalDateTime.ofInstant(Instant.ofEpochMilli(this), ZoneId.systemDefault())
    private fun LocalDateTime.toMillis(): Long = atZone(ZoneId.systemDefault()).toInstant().toEpochMilli()
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EventEditScreen(eventId: Long?, organizerId: Long, onDone: () -> Unit) {
    val vm = appViewModel(key = "edit-${eventId ?: "new"}") { EventEditViewModel(it.repository, eventId, organizerId) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (eventId == null) "Create event" else "Edit event") },
                navigationIcon = { IconButton(onClick = onDone) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        if (!vm.loaded) {
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
            OutlinedTextField(vm.title, { vm.title = it }, label = { Text("Event name") }, singleLine = true, modifier = Modifier.fillMaxWidth())
            CategoryDropdown(vm.category) { vm.category = it }
            OutlinedTextField(
                vm.description, { vm.description = it },
                label = { Text("Description") }, minLines = 3, modifier = Modifier.fillMaxWidth(),
            )
            OutlinedTextField(vm.venue, { vm.venue = it }, label = { Text("Venue") }, singleLine = true, modifier = Modifier.fillMaxWidth())

            DateTimeRow("Starts", vm.start) { vm.start = it; if (vm.end <= it) vm.end = it.plusHours(2) }
            DateTimeRow("Ends", vm.end) { vm.end = it }

            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedTextField(
                    vm.price, { vm.price = it.filter(Char::isDigit) },
                    label = { Text("Price (UGX)") }, singleLine = true,
                    supportingText = { Text("0 = free") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.weight(1f),
                )
                OutlinedTextField(
                    vm.capacity, { vm.capacity = it.filter(Char::isDigit) },
                    label = { Text("Seats") }, singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.weight(1f),
                )
            }
            if (eventId != null) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("Event cancelled (hidden from students)", Modifier.weight(1f))
                    Switch(checked = vm.cancelled, onCheckedChange = { vm.cancelled = it })
                }
            }
            ErrorText(vm.error)
            LoadingButton(if (eventId == null) "Publish event" else "Save changes", vm.saving, onClick = { vm.save(onDone) })
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun CategoryDropdown(selected: EventCategory, onSelect: (EventCategory) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = it }) {
        OutlinedTextField(
            value = selected.label, onValueChange = {}, readOnly = true,
            label = { Text("Category") },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded) },
            modifier = Modifier.menuAnchor(MenuAnchorType.PrimaryNotEditable).fillMaxWidth(),
        )
        ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            EventCategory.entries.forEach { c ->
                DropdownMenuItem(text = { Text(c.label) }, onClick = { onSelect(c); expanded = false })
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DateTimeRow(label: String, value: LocalDateTime, onChange: (LocalDateTime) -> Unit) {
    var pickDate by remember { mutableStateOf(false) }
    var pickTime by remember { mutableStateOf(false) }
    val millis = value.atZone(ZoneId.systemDefault()).toInstant().toEpochMilli()

    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        Text(label, Modifier.weight(0.6f))
        OutlinedButton(onClick = { pickDate = true }, Modifier.weight(1.4f)) { Text(Formatters.date(millis)) }
        OutlinedButton(onClick = { pickTime = true }, Modifier.weight(1f)) { Text(Formatters.time(millis)) }
    }

    if (pickDate) {
        // The date picker works in UTC midnights.
        val state = rememberDatePickerState(
            initialSelectedDateMillis = value.toLocalDate().atStartOfDay().toInstant(ZoneOffset.UTC).toEpochMilli(),
        )
        DatePickerDialog(
            onDismissRequest = { pickDate = false },
            confirmButton = {
                TextButton(onClick = {
                    state.selectedDateMillis?.let {
                        val date = Instant.ofEpochMilli(it).atZone(ZoneOffset.UTC).toLocalDate()
                        onChange(LocalDateTime.of(date, value.toLocalTime()))
                    }
                    pickDate = false
                }) { Text("OK") }
            },
            dismissButton = { TextButton(onClick = { pickDate = false }) { Text("Cancel") } },
        ) { DatePicker(state) }
    }

    if (pickTime) {
        val state = rememberTimePickerState(initialHour = value.hour, initialMinute = value.minute)
        AlertDialog(
            onDismissRequest = { pickTime = false },
            title = { Text("$label at") },
            text = { TimePicker(state) },
            confirmButton = {
                TextButton(onClick = {
                    onChange(value.withHour(state.hour).withMinute(state.minute))
                    pickTime = false
                }) { Text("OK") }
            },
            dismissButton = { TextButton(onClick = { pickTime = false }) { Text("Cancel") } },
        )
    }
}
