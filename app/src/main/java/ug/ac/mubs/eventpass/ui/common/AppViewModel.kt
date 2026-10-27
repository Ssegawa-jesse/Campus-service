package ug.ac.mubs.eventpass.ui.common

import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import ug.ac.mubs.eventpass.AppContainer
import ug.ac.mubs.eventpass.EventPassApp

/** Creates a ViewModel scoped to the current navigation entry, with access to the app's dependencies. */
@Composable
inline fun <reified VM : ViewModel> appViewModel(
    key: String? = null,
    crossinline create: (AppContainer) -> VM,
): VM {
    val container = (LocalContext.current.applicationContext as EventPassApp).container
    return viewModel(key = key, factory = viewModelFactory { initializer { create(container) } })
}
