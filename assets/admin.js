/**
 * JS funcionalidad del Link Shortener
 */
(function ($) {
  "use strict";

  // Variables globales
  var lsModal = null;

  $(document).ready(function () {
    initLinkShortener();

    // Debug: verificar que el nonce esté disponible
    var nonce = getLsNonce();
    if (!nonce) {
      console.warn("LS Warning: No se encontró nonce. AJAX puede fallar.");
      console.log(
        "lsAdmin object:",
        typeof lsAdmin !== "undefined" ? lsAdmin : "undefined"
      );
      console.log(
        "Hidden nonce field:",
        $("#ls_admin_nonce").length ? $("#ls_admin_nonce").val() : "not found"
      );
    } else {
      console.log(
        "LS Debug: Nonce encontrado:",
        nonce.substring(0, 10) + "..."
      );
    }
  });

  /**
   * Inicialización principal
   */
  function initLinkShortener() {
    // Eventos para generar enlaces
    $(document).on("click", ".ls-generate-link", handleGenerateLink);

    // Eventos para copiar enlaces
    $(document).on("click", ".ls-copy-link:not(.ls_link__btn)", handleCopyLink);

    // Eventos para copiar enlaces
    $(document).on("click", ".ls-copy-link.ls_link__btn", function (e) {
      handleCopyLink.call(this, e, true);
    });

    // Eventos para rotar slugs
    $(document).on("click", ".ls-rotate-slug", handleRotateSlug);

    // Eventos para regenerar enlaces
    $(document).on("click", ".ls-regenerate-link", handleRegenerateLink);

    // Eventos para eliminar enlaces
    $(document).on("click", ".ls-delete-link", handleDeleteLink);

    // Eventos para remover aliases
    $(document).on("click", ".ls-remove-alias", handleRemoveAlias);

    // Validación en tiempo real de slugs
    $(document).on("input", "#ls_slug", debounce(validateSlug, 300));

    // Validación inicial del slug al cargar la página (para casos de edición)
    if ($("#ls_slug").length) {
      var initialSlug = $("#ls_slug").val().trim();
      if (initialSlug) {
        validateSlug.call($("#ls_slug")[0]);
      }
    }

    // Validación en tiempo real de URLs
    $(document).on("input", "#ls_original_url", debounce(validateUrl, 500));

    // Preview del slug en tiempo real
    $(document).on("input", "#ls_slug", updateSlugPreview);

    // Configuración de modales
    initModals();

    // Configuración de prefijos en ajustes
    if (typeof lsSettings !== "undefined") {
      $(document).on("input", "#ls_current_prefix", updatePrefixPreview);
    }

    // Prevenir envío de formulario si el slug no es válido
    $(document).on("submit", "#post", function(e) {
      var $updateButton = $('#publish, #save-post');
      if ($updateButton.hasClass('ls-disabled')) {
        e.preventDefault();
        showNotice("No se puede guardar: el slug ya está en uso. Por favor, elige otro slug.", "error");
        return false;
      }
    });
  }

  /**
   * Obtiene el nonce para AJAX desde múltiples fuentes
   */
  function getLsNonce() {
    // Intentar desde el objeto localizado
    if (typeof lsAdmin !== "undefined" && lsAdmin.nonce) {
      return lsAdmin.nonce;
    }

    // Intentar desde el campo oculto
    var $nonce = $("#ls_admin_nonce");
    if ($nonce.length && $nonce.val()) {
      return $nonce.val();
    }

    // Intentar desde otro campo
    var $altNonce = $('input[name="ls_admin_nonce"]');
    if ($altNonce.length && $altNonce.val()) {
      return $altNonce.val();
    }

    console.error("LS Error: No se pudo encontrar el nonce");
    return "";
  }

  /**
   * Genera un enlace corto desde listados
   */
  function handleGenerateLink(e) {
    e.preventDefault();

    var $button = $(this);
    var postId = $button.data("post-id");
    var url = $button.data("url");

    if (!postId || !url) {
      showNotice(((lsAdmin&&lsAdmin.strings)?(lsAdmin.strings.errorPrefix + ' ' + lsAdmin.strings.errorIncompleteData):'Error: Incomplete data'), "error");
      return;
    }

    var nonce = getLsNonce();
    if (!nonce) {
      showNotice(((lsAdmin&&lsAdmin.strings)?(lsAdmin.strings.errorPrefix + ' ' + lsAdmin.strings.errorNoNonce):'Error: Security token missing'), "error");
      return;
    }

    // Deshabilitar botón y mostrar cargando
    $button.prop("disabled", true).text((lsAdmin&&lsAdmin.strings)?lsAdmin.strings.generating:'Generating...');

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "ls_generate_short_link",
        post_id: postId,
        url: url,
        nonce: nonce,
      },
      success: function (response) {
        if (response.success) {
          // Reemplazar el botón con el enlace generado
          $button.closest("td").html(response.data.html);
          showNotice(response.data.message, "success");
          
          // Copiar automáticamente al portapapeles si hay URL
          if (response.data.short_url) {
            copyToClipboardAuto(response.data.short_url);
          }
        } else {
          showNotice("Error: " + response.data, "error");
          $button.prop("disabled", false).text("Generar short link");
        }
      },
      error: function () {
        showNotice("Error de conexión", "error");
        $button.prop("disabled", false).text("Generar short link");
      },
    });
  }

  /**
   * Copia un enlace al portapapeles
   */
  function handleCopyLink(e, isButtonMain = false) {
    e.preventDefault();

    var $button = $(this);
    var url = $button.data("url");
    if (!url) {
      showNotice(((lsAdmin&&lsAdmin.strings)?(lsAdmin.strings.errorPrefix + ' ' + lsAdmin.strings.noUrlToCopy):'Error: No URL to copy'), "error");
      return;
    }

    // Usar la API del portapapeles si está disponible
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(url)
        .then(function () {
          showCopySuccess($button, url, isButtonMain);
        })
        .catch(function () {
          fallbackCopyToClipboard(url, $button);
        });
    } else {
      fallbackCopyToClipboard(url, $button);
    }
  }

  /**
   * Fallback para copiar al portapapeles
   */
  function fallbackCopyToClipboard(text, $button) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      document.execCommand("copy");
      showCopySuccess($button, text);
    } catch (err) {
      showNotice(
        (((lsAdmin && lsAdmin.strings) ? (lsAdmin.strings.copyFallbackError + ' ') : 'Copy failed. Select and copy manually: ') + text),
        "error"
      );
    }

    document.body.removeChild(textArea);
  }

  /**
   * Muestra feedback de copia exitosa
   */
  function showCopySuccess($button, url, isButtonMain = false) {
    if (isButtonMain === false) {
      var originalText = $button.text();
      $button.text(((lsAdmin && lsAdmin.strings) ? lsAdmin.strings.copySuccess : 'Copied!')).addClass("ls-copied");

      setTimeout(function () {
        $button.text(originalText).removeClass("ls-copied");
      }, 2000);

      showNotice((((lsAdmin && lsAdmin.strings) ? (lsAdmin.strings.linkCopiedPrefix + ' ') : 'Link copied: ') + url), "success", 3000);
    } else {
      showNotice((((lsAdmin && lsAdmin.strings) ? (lsAdmin.strings.linkCopiedPrefix + ' ') : 'Link copied: ') + url), "success", 3000);
    }
  }

  /**
   * Copia automáticamente al portapapeles después de generar enlace
   */
  function copyToClipboardAuto(url) {
    // Usar la API del portapapeles si está disponible
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(url)
        .then(function () {
          showNotice("Enlace generado y copiado al portapapeles: " + url, "success", 4000);
        })
        .catch(function () {
          fallbackCopyToClipboardAuto(url);
        });
    } else {
      fallbackCopyToClipboardAuto(url);
    }
  }

  /**
   * Fallback para copia automática al portapapeles
   */
  function fallbackCopyToClipboardAuto(url) {
    var textArea = document.createElement("textarea");
    textArea.value = url;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      document.execCommand("copy");
      showNotice("Enlace generado y copiado al portapapeles: " + url, "success", 4000);
    } catch (err) {
      showNotice("Enlace generado. No se pudo copiar automáticamente: " + url, "warning", 5000);
    }

    document.body.removeChild(textArea);
  }

  /**
   * Maneja la rotación de slugs
   */
  function handleRotateSlug(e) {
    e.preventDefault();

    var $button = $(this);
    var linkId = $button.data("link-id");
    var originalUrl = $button.data("original-url");

    if (!linkId) {
      showNotice("Error: ID de enlace requerido", "error");
      return;
    }

    showRotateSlugModal(linkId, originalUrl);
  }

  /**
   * Muestra el modal para rotar slug
   */
  function showRotateSlugModal(linkId, originalUrl) {
    var modalHtml = `
            <div class="ls-modal-overlay" id="ls-rotate-modal">
                <div class="ls-modal" role="dialog" aria-labelledby="ls-modal-title" aria-modal="true">
                    <div class="ls-modal-header">
                        <h2 id="ls-modal-title">Rotar Slug</h2>
                        <button type="button" class="ls-modal-close" aria-label="Cerrar modal">&times;</button>
                    </div>
                    <div class="ls-modal-body">
                        <p>Elige cómo quieres actualizar el slug de este enlace:</p>
                        
                        <div class="ls-rotate-options">
                            <div class="ls-option">
                                <label>
                                    <input type="radio" name="rotate_action" value="replace" checked>
                                    <strong>Reemplazar slug</strong>
                                    <span class="description">El slug anterior dejará de funcionar</span>
                                </label>
                            </div>
                            <div class="ls-option">
                                <label>
                                    <input type="radio" name="rotate_action" value="alias">
                                    <strong>Añadir alias</strong>
                                    <span class="description">El slug anterior seguirá funcionando</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="ls-new-slug-field">
                            <label for="ls-new-slug">Nuevo slug (opcional):</label>
                            <input type="text" id="ls-new-slug" placeholder="Dejar vacío para generar automáticamente">
                            <div id="ls-slug-validation-modal" class="ls-validation-message"></div>
                        </div>
                    </div>
                    <div class="ls-modal-footer">
                        <button type="button" class="button-primary ls-confirm-rotate" data-link-id="${linkId}">Rotar Slug</button>
                        <button type="button" class="button-secondary ls-modal-cancel">Cancelar</button>
                    </div>
                </div>
            </div>
        `;

    $("body").append(modalHtml);
    lsModal = $("#ls-rotate-modal");

    // Focus trap y eventos
    setupModalEvents(lsModal);

    // Validación del nuevo slug
    $("#ls-new-slug").on(
      "input",
      debounce(function () {
        var slug = $(this).val().trim();
        if (slug) {
          validateSlugAvailability(slug, linkId, "#ls-slug-validation-modal");
        } else {
          $("#ls-slug-validation-modal").hide();
        }
      }, 300)
    );
  }

  /**
   * Confirma la rotación de slug
   */
  $(document).on("click", ".ls-confirm-rotate", function () {
    var $button = $(this);
    var linkId = $button.data("link-id");
    var actionType = $('input[name="rotate_action"]:checked').val();
    var newSlug = $("#ls-new-slug").val().trim();

    $button.prop("disabled", true).text("Rotando...");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "ls_rotate_slug",
        link_id: linkId,
        action_type: actionType,
        new_slug: newSlug,
        nonce: getLsNonce(),
      },
      success: function (response) {
        if (response.success) {
          // Actualizar la interfaz
          var $linkDisplay = $(`[data-link-id="${linkId}"]`).closest(
            ".ls-short-link-display"
          );
          if ($linkDisplay.length) {
            $linkDisplay.replaceWith(response.data.html);
          }

          closeModal();
          showNotice(response.data.message, "success");
        } else {
          showNotice("Error: " + response.data, "error");
          $button.prop("disabled", false).text("Rotar Slug");
        }
      },
      error: function () {
        showNotice("Error de conexión", "error");
        $button.prop("disabled", false).text("Rotar Slug");
      },
    });
  });

  /**
   * Elimina un enlace
   */
  function handleDeleteLink(e) {
    e.preventDefault();

    var $button = $(this);
    var linkId = $button.data("link-id");

    if (!confirm(((lsAdmin && lsAdmin.strings) ? lsAdmin.strings.confirmDelete : 'Are you sure you want to delete this link? This action cannot be undone.'))) {
      return;
    }

    $button.prop("disabled", true).text(((lsAdmin && lsAdmin.strings) ? lsAdmin.strings.deleting : 'Deleting...'));

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "ls_delete_link",
        link_id: linkId,
        nonce: getLsNonce(),
      },
      success: function (response) {
        if (response.success) {
          // Remove the row or element
          $button
            .closest("tr, .ls-short-link-display")
            .fadeOut(300, function () {
              $(this).remove();
            });
          showNotice(response.data.message, "success");
        } else {
          showNotice(((lsAdmin && lsAdmin.strings) ? (lsAdmin.strings.errorPrefix + ' ') : 'Error: ') + response.data, "error");
          $button.prop("disabled", false).text(((lsAdmin && lsAdmin.strings) ? lsAdmin.strings.delete : 'Delete'));
        }
      },
      error: function () {
        showNotice(((lsAdmin && lsAdmin.strings) ? lsAdmin.strings.connectionError : 'Connection error'), "error");
        $button.prop("disabled", false).text(((lsAdmin && lsAdmin.strings) ? lsAdmin.strings.delete : 'Delete'));
      },
    });
  }

  /**
   * Regenera un enlace corto (crea uno nuevo sin eliminar el anterior)
   */
  function handleRegenerateLink(e) {
    e.preventDefault();

    var $button = $(this);
    var originalUrl = $button.data("original-url");

    if (!originalUrl) {
      showNotice("Error: URL original requerida", "error");
      return;
    }

    if (
      !confirm(
        "¿Generar un nuevo enlace corto? El enlace actual seguirá funcionando."
      )
    ) {
      return;
    }

    $button.prop("disabled", true).text("Regenerando...");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "ls_regenerate_link",
        original_url: originalUrl,
        nonce: getLsNonce(),
      },
      success: function (response) {
        if (response.success) {
          // Reemplazar el display actual con el nuevo enlace
          var $linkDisplay = $button.closest(".ls-short-link-display");
          if ($linkDisplay.length) {
            $linkDisplay.replaceWith(response.data.html);
          }

          showNotice(response.data.message, "success");
        } else {
          showNotice("Error: " + response.data, "error");
          $button.prop("disabled", false).text("Regenerar");
        }
      },
      error: function () {
        showNotice("Error de conexión", "error");
        $button.prop("disabled", false).text("Regenerar");
      },
    });
  }

  /**
   * Remueve un alias
   */
  function handleRemoveAlias(e) {
    e.preventDefault();

    var $button = $(this);
    var linkId = $button.data("post-id");
    var aliasSlug = $button.data("alias");

    if (!confirm("¿Eliminar este alias? El enlace dejará de funcionar.")) {
      return;
    }

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "ls_remove_alias",
        link_id: linkId,
        alias_slug: aliasSlug,
        nonce: getLsNonce(),
      },
      success: function (response) {
        if (response.success) {
          $button.closest(".ls-alias-item").fadeOut(300, function () {
            $(this).remove();
          });
          showNotice(response.data.message, "success");
        } else {
          showNotice("Error: " + response.data, "error");
        }
      },
    });
  }

  /**
   * Validación en tiempo real de slugs
   */
  function validateSlug() {
    var slug = $(this).val().trim();
    var excludeId = $('input[name="post_ID"]').val() || 0;

    if (!slug) {
      $("#ls_slug_validation").hide();
      toggleUpdateButton(true); // Habilitar botón si no hay slug (se generará automáticamente)
      return;
    }

    // Validación básica de formato
    if (!/^[a-zA-Z0-9\-_]+$/.test(slug)) {
      $("#ls_slug_validation")
        .removeClass("success")
        .addClass("error")
        .text("El slug solo puede contener letras, números, guiones y guiones bajos")
        .show();
      toggleUpdateButton(false);
      return;
    }

    validateSlugAvailability(slug, excludeId, "#ls_slug_validation");
  }

  /**
   * Valida disponibilidad de slug
   */
  function validateSlugAvailability(slug, excludeId, targetSelector) {
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "ls_check_slug",
        slug: slug,
        exclude_id: excludeId,
        nonce: getLsNonce(),
      },
      success: function (response) {
        var $target = $(targetSelector);

        if (response.success) {
          var data = response.data;
          var className = data.available ? "success" : "error";
          var message = data.message;

          if (!data.available && data.suggestion) {
            message += ". Sugerencia: " + data.suggestion;
          }

          $target
            .removeClass("success error")
            .addClass(className)
            .text(message)
            .show();

          // Controlar el botón de actualizar basado en disponibilidad
          if (targetSelector === "#ls_slug_validation") {
            toggleUpdateButton(data.available);
          }
        } else {
          $target
            .removeClass("success")
            .addClass("error")
            .text("Error al validar slug")
            .show();

          // Deshabilitar botón en caso de error
          if (targetSelector === "#ls_slug_validation") {
            toggleUpdateButton(false);
          }
        }
      },
      error: function() {
        var $target = $(targetSelector);
        $target
          .removeClass("success")
          .addClass("error")
          .text("Error de conexión al validar slug")
          .show();

        // Deshabilitar botón en caso de error de conexión
        if (targetSelector === "#ls_slug_validation") {
          toggleUpdateButton(false);
        }
      }
    });
  }

  /**
   * Valida URL en tiempo real
   */
  function validateUrl() {
    var $input = $(this);
    var url = $input.val().trim();
    var $target = $("#ls_url_validation");

    // Limpiar estados anteriores
    $input.removeClass("ls-valid ls-invalid");

    if (!url) {
      $target.hide();
      return;
    }

    // Validación básica del lado del cliente
    var urlPattern = /^https?:\/\/[^\s]+\.[^\s]+/;
    if (!urlPattern.test(url)) {
      $input.addClass("ls-invalid");
      $target
        .removeClass("success")
        .addClass("error")
        .text(
          "' + (((typeof lsAdmin!=='undefined'&&lsAdmin.strings)?lsAdmin.strings.urlMustStartWithHttp:'URL must start with http:// or https:// and include a valid domain')) + '"
        )
        .show();
      return;
    }

    // Validación adicional: verificar que no sea solo texto
    var domainPattern =
      /^https?:\/\/[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]?\.[a-zA-Z]{2,}/;
    if (!domainPattern.test(url)) {
      $input.addClass("ls-invalid");
      $target
        .removeClass("success")
        .addClass("error")
        .text("El dominio no parece ser válido")
        .show();
      return;
    }

    // Validación en el servidor
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "ls_validate_url",
        url: url,
        nonce: getLsNonce(),
      },
      success: function (response) {
        if (response.success) {
          var data = response.data;
          var className = data.valid ? "success" : "error";
          var inputClass = data.valid ? "ls-valid" : "ls-invalid";
          var message = data.message;

          $input.addClass(inputClass);

          if (!data.valid && data.existing_url) {
            message += ": " + data.existing_url;
          }

          $target
            .removeClass("success error")
            .addClass(className)
            .text(message)
            .show();
        } else {
          $input.addClass("ls-invalid");
          $target
            .removeClass("success")
            .addClass("error")
            .text("Error al validar URL")
            .show();
        }
      },
      error: function () {
        $input.addClass("ls-invalid");
        $target
          .removeClass("success")
          .addClass("error")
          .text("Error de conexión al validar URL")
          .show();
      },
    });
  }

  /**
   * Actualiza preview del slug
   */
  function updateSlugPreview() {
    var slug = $(this).val();
    var $preview = $("#ls_full_url");

    if ($preview.length && slug) {
      var baseUrl = $preview.text().replace(/\/[^\/]*$/, "/");
      $preview.text(baseUrl + slug);
    }
  }

  /**
   * Actualiza preview del prefijo en ajustes
   */
  function updatePrefixPreview() {
    if (typeof lsSettings === "undefined") return;

    var prefix = $(this).val();
    var $preview = $("#ls-preview-url");

    if ($preview.length) {
      var homeUrl = lsSettings.homeUrl;
      // Asegurar que homeUrl termine con slash
      if (!homeUrl.endsWith('/')) {
        homeUrl += '/';
      }
      
      if (prefix) {
        var newUrl = homeUrl + prefix + "/ejemplo";
        $preview.text(newUrl);
      } else {
        var newUrl = homeUrl + "l/ejemplo";
        $preview.text(newUrl);
      }
    }
  }

  /**
   * Configuración de modales
   */
  function initModals() {
    // Cerrar modal al hacer clic en overlay
    $(document).on("click", ".ls-modal-overlay", function (e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Cerrar modal con botón X o cancelar
    $(document).on("click", ".ls-modal-close, .ls-modal-cancel", closeModal);

    // Cerrar modal con ESC
    $(document).on("keydown", function (e) {
      if (e.keyCode === 27 && lsModal && lsModal.is(":visible")) {
        closeModal();
      }
    });
  }

  /**
   * Configura eventos y accesibilidad del modal
   */
  function setupModalEvents($modal) {
    // Focus en el primer elemento
    $modal.find("input, button").first().focus();

    // Focus trap
    var focusableElements = $modal.find(
      'input, button, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    var firstElement = focusableElements.first();
    var lastElement = focusableElements.last();

    $modal.on("keydown", function (e) {
      if (e.keyCode === 9) {
        // Tab
        if (e.shiftKey) {
          if (document.activeElement === firstElement[0]) {
            e.preventDefault();
            lastElement.focus();
          }
        } else {
          if (document.activeElement === lastElement[0]) {
            e.preventDefault();
            firstElement.focus();
          }
        }
      }
    });

    $modal.fadeIn(200);
  }

  /**
   * Cierra el modal actual
   */
  function closeModal() {
    if (lsModal) {
      lsModal.fadeOut(200, function () {
        lsModal.remove();
        lsModal = null;
      });
    }
  }

  /**
   * Muestra notificaciones
   */
  function showNotice(message, type, duration) {
    type = type || "info";
    duration = duration || 5000;

    var $notice = $(
      '<div class="ls-notice ls-notice-' + type + '">' + message + "</div>"
    );

    // Remover notificaciones existentes
    $(".ls-notice").remove();

    // Añadir nueva notificación
    $("body").append($notice);

    $notice
      .fadeIn(300)
      .delay(duration)
      .fadeOut(300, function () {
        $(this).remove();
      });
  }

  /**
   * Controla el estado del botón de actualizar/publicar
   */
  function toggleUpdateButton(enable) {
    var $updateButton = $('#publish, #save-post, .editor-post-publish-button, input[name="save"], input[type="submit"][name="publish"]');
    var $slugField = $('#ls_slug');
    
    if (enable) {
      $updateButton.prop('disabled', false).removeClass('ls-disabled');
      $slugField.removeClass('ls-slug-invalid').addClass('ls-slug-valid');
      
      // Remover el atributo data-invalid si existe
      $updateButton.removeAttr('data-invalid');
    } else {
      $updateButton.prop('disabled', true).addClass('ls-disabled');
      $slugField.removeClass('ls-slug-valid').addClass('ls-slug-invalid');
      
      // Agregar atributo para identificar el estado inválido
      $updateButton.attr('data-invalid', 'true');
    }
  }

  /**
   * Debounce function
   */
  function debounce(func, wait) {
    var timeout;
    return function executedFunction() {
      var context = this;
      var args = arguments;
      var later = function () {
        timeout = null;
        func.apply(context, args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
})(jQuery);
