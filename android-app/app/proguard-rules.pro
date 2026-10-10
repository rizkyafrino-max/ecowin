# EcoWin: aturan R8 untuk build release.

# Gson memetakan JSON ke field lewat refleksi + nama field (snake_case -> camelCase). Model API tidak boleh di-rename/di-hapus.
-keepattributes Signature, *Annotation*, InnerClasses, EnclosingMethod
-keep class id.ecowin.app.*Dto { *; }
-keep class id.ecowin.app.*Request { *; }
-keep class id.ecowin.app.*Response { *; }
-keep class id.ecowin.app.*Summary { *; }
-keep class id.ecowin.app.TokenPair { *; }
-keep class id.ecowin.app.ApiError { *; }
-keep class id.ecowin.app.Paged { *; }
-keep class id.ecowin.app.DataWrapper { *; }

# Retrofit: antarmuka API dan tipe generik pada respons.
-keep interface id.ecowin.app.EcoWinApi { *; }
-keepattributes RuntimeVisibleAnnotations, RuntimeVisibleParameterAnnotations
-dontwarn retrofit2.KotlinExtensions
-dontwarn retrofit2.KotlinExtensions$*
-if interface * { @retrofit2.http.* <methods>; }
-keep,allowobfuscation interface <1>
-keep,allowobfuscation,allowshrinking class kotlin.coroutines.Continuation

# OkHttp / Okio
-dontwarn okhttp3.internal.platform.**
-dontwarn org.conscrypt.**
-dontwarn org.bouncycastle.**
-dontwarn org.openjsse.**

# Gson TypeToken
-keep class com.google.gson.reflect.TypeToken { *; }
-keep class * extends com.google.gson.reflect.TypeToken
