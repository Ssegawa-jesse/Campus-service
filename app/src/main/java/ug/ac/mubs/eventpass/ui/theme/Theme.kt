package ug.ac.mubs.eventpass.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

val MubsBlue = Color(0xFF0B3D91)
val MubsBlueLight = Color(0xFF9DB8F2)
val MubsGold = Color(0xFFF2A900)
val SuccessGreen = Color(0xFF1B8A3A)
val WarningAmber = Color(0xFFB26A00)
val ErrorRed = Color(0xFFC62828)

private val LightColors = lightColorScheme(
    primary = MubsBlue,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFDCE5FB),
    onPrimaryContainer = Color(0xFF001A43),
    secondary = MubsGold,
    onSecondary = Color(0xFF2A1C00),
    secondaryContainer = Color(0xFFFFE3A6),
    onSecondaryContainer = Color(0xFF261900),
    background = Color(0xFFF8F9FD),
    surface = Color.White,
)

private val DarkColors = darkColorScheme(
    primary = MubsBlueLight,
    onPrimary = Color(0xFF00296B),
    primaryContainer = Color(0xFF1D4B9E),
    onPrimaryContainer = Color(0xFFDCE5FB),
    secondary = Color(0xFFFFC94D),
    onSecondary = Color(0xFF402D00),
    secondaryContainer = Color(0xFF5C4200),
    onSecondaryContainer = Color(0xFFFFE3A6),
)

@Composable
fun EventPassTheme(darkTheme: Boolean = isSystemInDarkTheme(), content: @Composable () -> Unit) {
    MaterialTheme(colorScheme = if (darkTheme) DarkColors else LightColors, content = content)
}
