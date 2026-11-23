# Barra de envío gratis: autoriza tu tienda (versión 1.1.0)

Desde la versión 1.1.0, la edición de descarga (GitHub / getdropflow.com) debe autorizarse una vez con una **cuenta gratuita de GetDropFlow**. La barra no cuesta nada; la cuenta solo sirve para saber qué tienda la usa. Mientras la tienda no esté autorizada, **la barra no se muestra a tus clientes**. El carrito, el pago, los pedidos, los precios y el envío nunca se tocan ni se bloquean, esté o no autorizada.

## Autoriza en 4 pasos

1. Abre **Stores > Configuration > Sales > Free Shipping Progress Bar**. Mientras la tienda no esté autorizada solo verás la tarjeta **Autorización** (y las tarjetas de Actualizaciones y Más herramientas).
2. Pulsa **Iniciar sesión en GetDropFlow para autorizar**. Se abre una pestaña nueva (si el navegador la bloquea, pulsa **Abrir de nuevo la página de autorización**). La página de ajustes muestra un código como `BCDF-GHJK`.
3. En la página de GetDropFlow inicia sesión (o crea una cuenta gratuita), comprueba que el código es el mismo que ves en el administrador y pulsa **Permitir**.
4. Vuelve al administrador: en unos segundos la tarjeta dice **Esta tienda está autorizada** y aparecen los ajustes de la barra. No hay que copiar ninguna clave.

## Qué se comparte

Solo la **dirección web de tu tienda** y el **correo de la cuenta** con la que inicias sesión. La tienda guarda su propio token (no la clave de tu cuenta) y no se lee nada más de tu tienda: ni pedidos, ni clientes, ni productos. Detalles: la página de privacidad de getdropflow.com.

## Reglas (qué pasa y cuándo)

- **Tienda nueva, sin autorizar**: solo la tarjeta de autorización en los ajustes; la barra está oculta en la tienda. Carrito, pago y pedidos funcionan como siempre.
- **Basta la membresía gratuita.** No se cobra nada. Un plan de pago de GetDropFlow también sirve.
- **No se puede conectar con el sitio web** (tu servidor sin conexión, el sitio caído): una tienda autorizada sigue funcionando **30 días** desde la última comprobación correcta; la tarjeta muestra los días que quedan. La comprobación se repite sola cada 6 horas. Solo la respuesta clara del sitio "esta tienda fue eliminada / revocada" bloquea la tienda al instante.
- **Un plan de pago terminó**: la tarjeta muestra una nota azul. La membresía gratuita continúa, la barra sigue funcionando y no se bloquea nada.
- **Desconectar esta tienda** (botón de la tarjeta) borra la credencial guardada en tu tienda; la barra se oculta hasta que vuelvas a autorizar. También puedes quitar la tienda en tu cuenta de GetDropFlow; la tienda se bloquea en la siguiente comprobación (hasta 6 horas, o pulsa **Comprobar ahora**).
- **Al actualizar desde 1.0.0**: una tienda sin autorización se bloquea tras la actualización (la barra desaparece de la tienda) hasta que la autorices una vez. Haz los 4 pasos anteriores después de actualizar.
- **Edición Marketplace** (Adobe Commerce Marketplace): no necesita autorización, no puede conectarse con el sitio web.
- **Avanzado**: si ya tienes una clave de licencia de GetDropFlow, abre **Avanzado: introducir una clave a mano**, escríbela y pulsa **Guardar y comprobar**. Lo normal es usar el botón de arriba.

## Problemas frecuentes

| | |
|---|---|
| **El botón no hace nada / no se abre la pestaña** | El navegador bloqueó la ventana emergente. Pulsa **Abrir de nuevo la página de autorización** o permite las ventanas emergentes para la dirección de tu administrador. |
| **"Este servidor no puede conectar con el sitio de GetDropFlow ahora mismo"** | Tu servidor no puede abrir https://license.getdropflow.com (cortafuegos, DNS, conexiones salientes bloqueadas). Pide a tu proveedor de hosting que permita https saliente a esa dirección y vuelve a intentarlo. Una tienda ya autorizada sigue funcionando 30 días. |
| **"El código caducó"** | Pasaron más de 10 minutos. Pulsa otra vez el botón de inicio de sesión; se crea un código nuevo. |
| **"Pulsaste Denegar"** | No se cambió nada. Empieza de nuevo y pulsa **Permitir**. |
| **"Demasiados intentos"** | Espera unos minutos. El sitio limita cuántas veces puede preguntar una tienda. |
| **"El sitio de GetDropFlow ya no conoce esta tienda"** | La tienda se eliminó en tu cuenta o la cuenta se cerró. Inicia sesión de nuevo para autorizarla. |
| **La tarjeta dice "autorizada" pero no se ve la barra** | Recarga la página; los datos de la barra se cargan en el navegador y se guardan en caché. Borra una vez el almacenamiento local del navegador (Magento guarda allí la respuesta anterior) o abre una ventana privada. |
| **"La clave se guardó, pero el sitio aún no pudo confirmarla"** | Pulsa **Comprobar ahora** en un minuto. Si sigue igual, la clave no es válida para la dirección de esta tienda. |

## Capturas (haz clic en una imagen para ampliarla)

Las imágenes las añade la tarea de capturas (recuadro rojo en el punto clave).

<a href="../images/auth-01-locked-card.es.png"><img src="../images/auth-01-locked-card.es.png" alt="Página de ajustes con la tienda sin autorizar: solo la tarjeta de autorización (más Actualizaciones y Más herramientas)." width="360"></a><br><sub>1. Página de ajustes con la tienda sin autorizar: solo la tarjeta de autorización (más Actualizaciones y Más herramientas).</sub>

<a href="../images/auth-02-code-waiting.es.png"><img src="../images/auth-02-code-waiting.es.png" alt="Tras pulsar el botón: el código y el texto de espera." width="360"></a><br><sub>2. Tras pulsar el botón: el código y el texto de espera.</sub>

<a href="../images/auth-03-website-allow.es.png"><img src="../images/auth-03-website-allow.es.png" alt="Página de GetDropFlow: qué se comparte (dirección de la tienda y correo de la cuenta), el código y el botón Permitir." width="360"></a><br><sub>3. Página de GetDropFlow: qué se comparte (dirección de la tienda y correo de la cuenta), el código y el botón Permitir.</sub>

<a href="../images/auth-04-authorized.es.png"><img src="../images/auth-04-authorized.es.png" alt="La tarjeta tras autorizar: "Esta tienda está autorizada" y la membresía." width="360"></a><br><sub>4. La tarjeta tras autorizar: "Esta tienda está autorizada" y la membresía.</sub>

<a href="../images/auth-05-offline.es.png"><img src="../images/auth-05-offline.es.png" alt="La tarjeta cuando no se puede conectar con el sitio: días restantes." width="360"></a><br><sub>5. La tarjeta cuando no se puede conectar con el sitio: días restantes.</sub>

<a href="../images/auth-06-lapsed.es.png"><img src="../images/auth-06-lapsed.es.png" alt="La nota azul cuando termina un plan de pago (la barra gratuita sigue funcionando)." width="360"></a><br><sub>6. La nota azul cuando termina un plan de pago (la barra gratuita sigue funcionando).</sub>

<a href="../images/auth-07-rejected.es.png"><img src="../images/auth-07-rejected.es.png" alt="El mensaje cuando el sitio ya no conoce la tienda." width="360"></a><br><sub>7. El mensaje cuando el sitio ya no conoce la tienda.</sub>

<a href="../images/auth-08-advanced-key.es.png"><img src="../images/auth-08-advanced-key.es.png" alt=""Avanzado: introducir una clave a mano" abierto." width="360"></a><br><sub>8. "Avanzado: introducir una clave a mano" abierto.</sub>

<a href="../images/auth-09-storefront-locked.es.png"><img src="../images/auth-09-storefront-locked.es.png" alt="Carrito de la tienda bloqueada: sin barra, carrito y pago funcionan como siempre." width="360"></a><br><sub>9. Carrito de la tienda bloqueada: sin barra, carrito y pago funcionan como siempre.</sub>

