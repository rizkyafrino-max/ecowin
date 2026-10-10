plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("org.jetbrains.kotlin.plugin.compose")
}

// Konfigurasi dari gradle.properties (atau ~/.gradle/gradle.properties):
//   ECOWIN_API_BASE_URL  = alamat API Laravel, diakhiri "/api/"
//   ECOWIN_GOOGLE_WEB_CLIENT_ID = OAuth client ID tipe "Web application" (sama dengan GOOGLE_CLIENT_ID di .env Laravel)
val apiBaseUrl: String = (project.findProperty("ECOWIN_API_BASE_URL") as String?) ?: "http://10.0.2.2:8000/api/"
val googleWebClientId: String = (project.findProperty("ECOWIN_GOOGLE_WEB_CLIENT_ID") as String?) ?: ""

val taskDir = gradle.startParameter.taskNames
if (taskDir.any { it.contains("Release", ignoreCase = true) && !it.contains("releaseCheck", ignoreCase = true) } && !apiBaseUrl.startsWith("https://")) {
    throw GradleException("Build release wajib memakai HTTPS: set ECOWIN_API_BASE_URL=https://domain-anda/api/ di gradle.properties.")
}

android {
    namespace = "id.ecowin.app"
    compileSdk = 35

    defaultConfig {
        applicationId = "id.ecowin.app"
        minSdk = 26
        targetSdk = 35
        versionCode = 2
        versionName = "2.0"
        buildConfigField("String", "API_BASE_URL", "\"$apiBaseUrl\"")
        buildConfigField("String", "GOOGLE_WEB_CLIENT_ID", "\"$googleWebClientId\"")
    }

    buildTypes {
        debug {
            // HTTP biasa hanya diizinkan saat development (server Laravel lokal).
            manifestPlaceholders["usesCleartextTraffic"] = "true"
        }
        release {
            // R8 + penyusutan resource; HTTP biasa dimatikan. Penandatanganan memakai keystore dari gradle.properties
            // pengguna (ECOWIN_KEYSTORE_FILE/PASSWORD, ECOWIN_KEY_ALIAS/PASSWORD): tidak pernah disimpan di repo.
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            manifestPlaceholders["usesCleartextTraffic"] = "false"
            val ks = project.findProperty("ECOWIN_KEYSTORE_FILE") as String?
            if (!ks.isNullOrBlank()) {
                signingConfig = signingConfigs.create("ecowinRelease") {
                    storeFile = file(ks)
                    storePassword = project.findProperty("ECOWIN_KEYSTORE_PASSWORD") as String?
                    keyAlias = project.findProperty("ECOWIN_KEY_ALIAS") as String?
                    keyPassword = project.findProperty("ECOWIN_KEY_PASSWORD") as String?
                }
            }
        }
        // Hanya untuk menguji hasil R8 di perangkat sendiri: sama dengan release tetapi memakai kunci debug
        // (SHA-1 sama, jadi login Google tetap jalan). JANGAN dibagikan.
        create("releaseCheck") {
            initWith(getByName("release"))
            signingConfig = signingConfigs.getByName("debug")
            matchingFallbacks += "release"
            // Hanya build uji lokal ini yang boleh HTTP (server Laravel lokal); release asli tetap HTTPS saja.
            manifestPlaceholders["usesCleartextTraffic"] = "true"
        }
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_1_8
        targetCompatibility = JavaVersion.VERSION_1_8
    }
}

kotlin {
    compilerOptions {
        jvmTarget.set(org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_1_8)
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("androidx.core:core-splashscreen:1.0.1")
    implementation("androidx.activity:activity-compose:1.10.0")
    implementation("androidx.compose.ui:ui:1.7.8")
    implementation("androidx.compose.material3:material3:1.3.1")
    implementation("androidx.compose.material:material-icons-core:1.7.8")
    implementation("androidx.lifecycle:lifecycle-runtime-compose:2.8.7")
    implementation("com.squareup.retrofit2:retrofit:2.11.0")
    implementation("com.squareup.retrofit2:converter-gson:2.11.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

    // Google Sign-In (Credential Manager)
    implementation("androidx.credentials:credentials:1.3.0")
    implementation("androidx.credentials:credentials-play-services-auth:1.3.0")
    implementation("com.google.android.libraries.identity.googleid:googleid:1.1.1")

    // Rotasi foto kamera sebelum kompresi
    implementation("androidx.exifinterface:exifinterface:1.3.7")

    testImplementation("junit:junit:4.13.2")
}
