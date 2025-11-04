/**
 * FF - Funcionalidad JavaScript para el admin del plugin
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // Funcionalidad para copiar enlaces
    $(".copy-link").on("click", function (e) {
      e.preventDefault();

      var url = $(this).data("url");
      var $button = $(this);

      // Crear elemento temporal para copiar
      var $temp = $("<input>");
      $("body").append($temp);
      $temp.val(url).select();

      try {
        document.execCommand("copy");

        // Cambiar texto del botón temporalmente
        var originalText = $button.text();
        $button.text(((window.lsAdmin&&lsAdmin.strings)?lsAdmin.strings.copySuccess:'Copied!')).addClass("copied");

        setTimeout(function () {
          $button.text(originalText).removeClass("copied");
        }, 2000);
      } catch (err) {
        alert(
          "No se pudo copiar el enlace. Por favor cópialo manualmente: " + url
        );
      }
      $temp.remove();
    });

    // Mejorar la búsqueda en tiempo real
    // var searchTimeout;
    // $("#link-search-input").on("keyup", function () {
    //   clearTimeout(searchTimeout);
    //   var $this = $(this);

    //   searchTimeout = setTimeout(function () {
    //     if ($this.val().length >= 3 || $this.val().length === 0) {
    //       $this.closest("form").submit();
    //     }
    //   }, 500);
    // });

    // Confirmación para acciones de eliminación
    $(".submitdelete").on("click", function (e) {
      if (
        !confirm(
          "¿Estás seguro de que quieres mover este enlace a la papelera?"
        )
      ) {
        e.preventDefault();
        return false;
      }
    });

    // Validación del formulario de nuevo enlace
    $("#link_shortener_form").on("submit", function (e) {
      var originalUrl = $("#original_url").val();

      if (!originalUrl) {
        alert(((window.lsAdmin && lsAdmin.strings && lsAdmin.strings.pleaseEnterOriginalUrl) ? lsAdmin.strings.pleaseEnterOriginalUrl : 'Please enter an original URL.'));
        $("#original_url").focus();
        e.preventDefault();
        return false;
      }

      // Validar formato de URL
      try {
        new URL(originalUrl);
      } catch (_) {
        alert(((window.lsAdmin && lsAdmin.strings && lsAdmin.strings.pleaseEnterValidUrl) ? lsAdmin.strings.pleaseEnterValidUrl : 'Please enter a valid URL (must start with http:// or https://).'));
        $("#original_url").focus();
        e.preventDefault();
        return false;
      }
    });

  });
})(jQuery);
