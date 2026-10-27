package ug.ac.mubs.eventpass.ui.auth

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
import androidx.compose.material.icons.filled.ConfirmationNumber
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.SegmentedButton
import androidx.compose.material3.SegmentedButtonDefaults
import androidx.compose.material3.SingleChoiceSegmentedButtonRow
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch
import ug.ac.mubs.eventpass.data.DemoDataSeeder
import ug.ac.mubs.eventpass.data.EventPassRepository
import ug.ac.mubs.eventpass.data.Role
import ug.ac.mubs.eventpass.data.SessionManager
import ug.ac.mubs.eventpass.ui.common.ErrorText
import ug.ac.mubs.eventpass.ui.common.LoadingButton
import ug.ac.mubs.eventpass.ui.common.appViewModel

class AuthViewModel(private val repository: EventPassRepository, private val session: SessionManager) : ViewModel() {
    var loading by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set

    fun login(email: String, password: String) = launchAuth { repository.login(email, password) }

    fun register(name: String, email: String, studentNo: String, password: String, role: Role, organizerCode: String) =
        launchAuth { repository.register(name, email, studentNo, password, role, organizerCode) }

    private fun launchAuth(block: suspend () -> Result<ug.ac.mubs.eventpass.data.User>) {
        if (loading) return
        loading = true
        error = null
        viewModelScope.launch {
            block()
                .onSuccess { session.login(it.id) }
                .onFailure { error = it.message ?: "Something went wrong" }
            loading = false
        }
    }

    fun clearError() {
        error = null
    }
}

@Composable
fun AuthScreen() {
    val vm = appViewModel { AuthViewModel(it.repository, it.session) }
    var showRegister by rememberSaveable { mutableStateOf(false) }

    Column(
        Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .imePadding()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Spacer(Modifier.height(32.dp))
        Icon(
            Icons.Filled.ConfirmationNumber,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(64.dp),
        )
        Text("Event Pass MUBS", style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
        Text(
            "Campus events and digital tickets",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Spacer(Modifier.height(32.dp))

        if (showRegister) {
            RegisterForm(vm)
        } else {
            LoginForm(vm)
        }

        TextButton(onClick = { showRegister = !showRegister; vm.clearError() }) {
            Text(if (showRegister) "Already have an account? Log in" else "New here? Create an account")
        }

        if (!showRegister) {
            Spacer(Modifier.height(16.dp))
            DemoAccountsCard()
        }
    }
}

@Composable
private fun LoginForm(vm: AuthViewModel) {
    var email by rememberSaveable { mutableStateOf("") }
    var password by rememberSaveable { mutableStateOf("") }

    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
        OutlinedTextField(
            value = email, onValueChange = { email = it },
            label = { Text("Email") }, singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            modifier = Modifier.fillMaxWidth(),
        )
        PasswordField(password, { password = it })
        ErrorText(vm.error)
        LoadingButton("Log in", vm.loading, onClick = { vm.login(email, password) })
    }
}

@Composable
private fun RegisterForm(vm: AuthViewModel) {
    var role by rememberSaveable { mutableStateOf(Role.STUDENT) }
    var name by rememberSaveable { mutableStateOf("") }
    var email by rememberSaveable { mutableStateOf("") }
    var studentNo by rememberSaveable { mutableStateOf("") }
    var password by rememberSaveable { mutableStateOf("") }
    var orgCode by rememberSaveable { mutableStateOf("") }

    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
        SingleChoiceSegmentedButtonRow(Modifier.fillMaxWidth()) {
            Role.entries.forEachIndexed { index, r ->
                SegmentedButton(
                    selected = role == r,
                    onClick = { role = r },
                    shape = SegmentedButtonDefaults.itemShape(index, Role.entries.size),
                ) { Text(if (r == Role.STUDENT) "Student" else "Organizer") }
            }
        }
        OutlinedTextField(name, { name = it }, label = { Text("Full name") }, singleLine = true, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(
            email, { email = it }, label = { Text("Email") }, singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            modifier = Modifier.fillMaxWidth(),
        )
        if (role == Role.STUDENT) {
            OutlinedTextField(
                studentNo, { studentNo = it }, label = { Text("Student number") }, singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                modifier = Modifier.fillMaxWidth(),
            )
        } else {
            OutlinedTextField(
                orgCode, { orgCode = it }, label = { Text("Organizer access code") }, singleLine = true,
                supportingText = { Text("Issued by the MUBS events office to authorised organizers") },
                modifier = Modifier.fillMaxWidth(),
            )
        }
        PasswordField(password, { password = it })
        ErrorText(vm.error)
        LoadingButton("Create account", vm.loading, onClick = { vm.register(name, email, studentNo, password, role, orgCode) })
    }
}

@Composable
private fun PasswordField(value: String, onChange: (String) -> Unit) {
    var visible by remember { mutableStateOf(false) }
    OutlinedTextField(
        value = value, onValueChange = onChange,
        label = { Text("Password") }, singleLine = true,
        visualTransformation = if (visible) VisualTransformation.None else PasswordVisualTransformation(),
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
        trailingIcon = {
            IconButton(onClick = { visible = !visible }) {
                Icon(
                    if (visible) Icons.Filled.VisibilityOff else Icons.Filled.Visibility,
                    contentDescription = if (visible) "Hide password" else "Show password",
                )
            }
        },
        modifier = Modifier.fillMaxWidth(),
    )
}

@Composable
private fun DemoAccountsCard() {
    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer)) {
        Column(Modifier.padding(16.dp).fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text("Demo accounts", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Row { Text("Student: ", fontWeight = FontWeight.SemiBold); Text("${DemoDataSeeder.DEMO_STUDENT_EMAIL} / ${DemoDataSeeder.DEMO_STUDENT_PASSWORD}") }
            Row { Text("Organizer: ", fontWeight = FontWeight.SemiBold); Text("${DemoDataSeeder.DEMO_ORGANIZER_EMAIL} / ${DemoDataSeeder.DEMO_ORGANIZER_PASSWORD}") }
            Text(
                "Organizer access code for new organizer accounts: ${EventPassRepository.ORGANIZER_ACCESS_CODE}",
                style = MaterialTheme.typography.bodySmall,
            )
        }
    }
}
