/**
 * Chemical Connect - front-end behaviour
 * Handles: AJAX comment posting, chat send/poll, like toggle,
 * media file preview, admin thread switching.
 */

document.addEventListener('DOMContentLoaded', function () {

  /* ---------------- Dynamic login ---------------- */
  var loginForm = document.getElementById('loginForm');
  if (loginForm) {
    var loginEmail = loginForm.querySelector('#email');
    var loginPassword = loginForm.querySelector('#password');
    var passwordToggle = document.getElementById('passwordToggle');
    var loginFeedback = document.getElementById('loginFeedback');
    var loginSubmit = document.getElementById('loginSubmit');
    var loginButtonLabel = loginSubmit.querySelector('.button-label');
    var serverAlerts = document.querySelectorAll('.login-card > .alert');

    function showLoginFeedback(message, kind) {
      loginFeedback.textContent = message;
      loginFeedback.className = 'login-feedback ' + kind;
      loginFeedback.hidden = false;
    }

    function clearLoginFeedback() {
      loginFeedback.hidden = true;
      loginFeedback.textContent = '';
      loginFeedback.className = 'login-feedback';
      serverAlerts.forEach(function (alert) { alert.remove(); });
    }

    function validateEmail(showError) {
      var valid = loginEmail.value.trim() !== '' && loginEmail.validity.valid;
      loginEmail.setAttribute('aria-invalid', String(showError && !valid));
      return valid;
    }

    loginEmail.addEventListener('blur', function () { validateEmail(true); });
    loginEmail.addEventListener('input', function () {
      validateEmail(false);
      clearLoginFeedback();
    });
    loginPassword.addEventListener('input', clearLoginFeedback);

    passwordToggle.addEventListener('click', function () {
      var reveal = loginPassword.type === 'password';
      loginPassword.type = reveal ? 'text' : 'password';
      passwordToggle.textContent = reveal ? 'Hide' : 'Show';
      passwordToggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
      passwordToggle.setAttribute('aria-pressed', String(reveal));
      loginPassword.focus();
    });

    loginForm.addEventListener('submit', function (event) {
      event.preventDefault();
      clearLoginFeedback();
      validateEmail(true);

      if (!loginForm.reportValidity()) return;

      loginSubmit.disabled = true;
      loginSubmit.classList.add('is-loading');
      loginButtonLabel.textContent = 'Signing in';

      fetch(loginForm.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: new FormData(loginForm)
      })
        .then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok || !data.success) {
              throw new Error((data.errors || ['Unable to sign in. Please try again.']).join(' '));
            }
            return data;
          });
        })
        .then(function (data) {
          showLoginFeedback('Signed in. Redirecting...', 'is-success');
          window.location.assign(data.redirect);
        })
        .catch(function (error) {
          showLoginFeedback(error.message || 'Unable to sign in. Please try again.', 'is-error');
          loginSubmit.disabled = false;
          loginSubmit.classList.remove('is-loading');
          loginButtonLabel.textContent = 'Log in';
        });
    });
  }

  /* ---------------- User dropdown ---------------- */
  document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      btn.closest('.dropdown').classList.toggle('open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.dropdown.open').forEach(function (d) { d.classList.remove('open'); });
  });

  /* ---------------- File preview on composer ---------------- */
  document.querySelectorAll('.media-input').forEach(function (input) {
    input.addEventListener('change', function () {
      var preview = input.closest('form').querySelector('.file-preview');
      if (preview) {
        preview.textContent = input.files.length ? ('Selected: ' + input.files[0].name) : '';
      }
    });
  });

  /* ---------------- Profile and cover photo adjustment ---------------- */
  var photoDialog = document.getElementById('photo-adjust-dialog');
  if (photoDialog) {
    var photoCanvas = document.getElementById('photo-adjust-preview');
    var photoContext = photoCanvas.getContext('2d');
    var photoZoom = document.getElementById('photo-adjust-zoom');
    var photoPositionX = document.getElementById('photo-adjust-x');
    var photoPositionY = document.getElementById('photo-adjust-y');
    var photoError = document.getElementById('photo-adjust-error');
    var applyPhotoButton = photoDialog.querySelector('.photo-adjust-apply');
    var activePhotoForm = null;
    var selectedPhoto = null;
    var acceptedPhoto = null;
    var objectUrl = null;

    function getPhotoCropSize() {
      if (activePhotoForm.dataset.photoType !== 'cover') {
        return { width: 512, height: 512 };
      }

      var cover = document.querySelector('.profile-cover');
      var width = 1200;
      var height = Math.max(1, Math.round(width * cover.clientHeight / cover.clientWidth));
      return { width: width, height: height };
    }

    function renderPhotoCrop() {
      if (!selectedPhoto) return;
      var cropSize = getPhotoCropSize();
      var width = cropSize.width;
      var height = cropSize.height;
      var scale = Math.max(width / selectedPhoto.naturalWidth, height / selectedPhoto.naturalHeight)
        * Number(photoZoom.value);
      var imageWidth = selectedPhoto.naturalWidth * scale;
      var imageHeight = selectedPhoto.naturalHeight * scale;
      var overflowX = imageWidth - width;
      var overflowY = imageHeight - height;
      var offsetX = overflowX * (Number(photoPositionX.value) + 100) / 200;
      var offsetY = overflowY * (Number(photoPositionY.value) + 100) / 200;

      photoCanvas.width = width;
      photoCanvas.height = height;
      photoContext.clearRect(0, 0, width, height);
      photoContext.drawImage(selectedPhoto, -offsetX, -offsetY, imageWidth, imageHeight);
    }

    function releasePhotoUrl() {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      }
    }

    function showPhotoPreview(form, file) {
      if (form._previewUrl) {
        URL.revokeObjectURL(form._previewUrl);
        form._previewUrl = null;
      }
      if (file) {
        form._previewUrl = URL.createObjectURL(file);
      }

      if (form.dataset.photoType === 'cover') {
        document.querySelector('.profile-cover').style.backgroundImage = file
          ? 'url("' + form._previewUrl + '")'
          : form._initialBackground;
      } else {
        document.querySelector('.avatar-xl').src = file ? form._previewUrl : form._initialAvatar;
      }
    }

    function restoreAcceptedPhoto() {
      var input = activePhotoForm.querySelector('input[type="file"]');
      var adjustButton = activePhotoForm.querySelector('.photo-adjust-open');
      if (acceptedPhoto) {
        var files = new DataTransfer();
        files.items.add(acceptedPhoto);
        input.files = files.files;
        adjustButton.hidden = false;
      } else {
        input.value = '';
        adjustButton.hidden = true;
      }
      showPhotoPreview(activePhotoForm, acceptedPhoto);
      selectedPhoto = null;
      releasePhotoUrl();
      photoDialog.close();
    }

    function openPhotoAdjuster(form, file) {
      activePhotoForm = form;
      acceptedPhoto = form._acceptedPhoto || null;
      selectedPhoto = new Image();
      photoError.hidden = true;
      photoError.textContent = '';
      photoZoom.value = '1';
      photoPositionX.value = '0';
      photoPositionY.value = '0';
      releasePhotoUrl();
      objectUrl = URL.createObjectURL(file);
      selectedPhoto.onload = function () {
        renderPhotoCrop();
        photoDialog.showModal();
      };
      selectedPhoto.onerror = function () {
        photoError.textContent = 'This image could not be opened. Please choose another image.';
        photoError.hidden = false;
        restoreAcceptedPhoto();
      };
      selectedPhoto.src = objectUrl;
    }

    document.querySelectorAll('.profile-photo-form').forEach(function (form) {
      var input = form.querySelector('input[type="file"]');
      var adjustButton = form.querySelector('.photo-adjust-open');

      form.dataset.photoType = form.querySelector('input[name="photo_type"]').value;
      form._initialBackground = document.querySelector('.profile-cover').style.backgroundImage;
      form._initialAvatar = document.querySelector('.avatar-xl').src;
      input.addEventListener('change', function () {
        if (input.files.length) openPhotoAdjuster(form, input.files[0]);
      });
      adjustButton.addEventListener('click', function () {
        if (input.files.length) openPhotoAdjuster(form, input.files[0]);
      });
      form.addEventListener('submit', function (event) {
        if (input.files.length && !form._acceptedPhoto) {
          event.preventDefault();
          openPhotoAdjuster(form, input.files[0]);
        }
      });
    });

    [photoZoom, photoPositionX, photoPositionY].forEach(function (control) {
      control.addEventListener('input', renderPhotoCrop);
    });

    photoDialog.querySelector('.photo-adjust-cancel').addEventListener('click', restoreAcceptedPhoto);
    photoDialog.addEventListener('cancel', function (event) {
      event.preventDefault();
      restoreAcceptedPhoto();
    });
    applyPhotoButton.addEventListener('click', function () {
      if (!activePhotoForm || !selectedPhoto) return;
      applyPhotoButton.disabled = true;
      photoCanvas.toBlob(function (blob) {
        applyPhotoButton.disabled = false;
        if (!blob) {
          photoError.textContent = 'Could not prepare the adjusted photo. Please try again.';
          photoError.hidden = false;
          return;
        }

        var input = activePhotoForm.querySelector('input[type="file"]');
        var outputName = activePhotoForm.dataset.photoType === 'cover' ? 'cover-photo.jpg' : 'profile-photo.jpg';
        var croppedFile = new File([blob], outputName, { type: 'image/jpeg' });
        var files = new DataTransfer();
        files.items.add(croppedFile);
        input.files = files.files;
        activePhotoForm._acceptedPhoto = croppedFile;
        activePhotoForm.querySelector('.photo-adjust-open').hidden = false;

        var previewData = photoCanvas.toDataURL('image/jpeg', 0.9);
        if (activePhotoForm._previewUrl) {
          URL.revokeObjectURL(activePhotoForm._previewUrl);
          activePhotoForm._previewUrl = null;
        }
        if (activePhotoForm.dataset.photoType === 'cover') {
          document.querySelector('.profile-cover').style.backgroundImage = 'url("' + previewData + '")';
        } else {
          document.querySelector('.avatar-xl').src = previewData;
        }

        selectedPhoto = null;
        releasePhotoUrl();
        photoDialog.close();
      }, 'image/jpeg', 0.9);
    });
  }

  /* ---------------- Like toggle ---------------- */
  document.querySelectorAll('.like-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var postId = btn.dataset.postId;
      fetch((window.API_PREFIX || '') + 'api/toggle_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'post_id=' + encodeURIComponent(postId)
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            btn.classList.toggle('liked', data.liked);
            var countEl = document.querySelector('.like-count-' + postId);
            if (countEl) countEl.textContent = data.count;
          }
        });
    });
  });

  /* ---------------- Comment submit (AJAX) ---------------- */
  document.querySelectorAll('.comment-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = form.querySelector('input[name="comment"]');
      var text = input.value.trim();
      if (!text) return;

      var formData = new FormData(form);
      formData.set('comment', text);

      fetch((window.API_PREFIX || '') + 'api/add_comment.php', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            var list = form.closest('.comment-section').querySelector('.comment-list');
            list.insertAdjacentHTML('beforeend', data.html);
            list.scrollTop = list.scrollHeight;
            input.value = '';
          } else {
            alert(data.message || 'Could not post comment.');
          }
        });
    });
  });

  /* ---------------- Admin: thread pill switching ---------------- */
  document.querySelectorAll('.thread-user-pill').forEach(function (pill) {
    pill.addEventListener('click', function () {
      var postId = pill.dataset.postId;
      var group = document.querySelector('.thread-group-' + postId);
      group.querySelectorAll('.thread-user-pill').forEach(function (p) { p.classList.remove('active'); });
      pill.classList.add('active');
      group.querySelectorAll('.thread-panel').forEach(function (p) { p.style.display = 'none'; });
      var target = group.querySelector('.thread-panel-' + pill.dataset.ownerId);
      if (target) target.style.display = 'block';
    });
  });

  /* ---------------- Chat widget ---------------- */
  document.querySelectorAll('.chat-widget .chat-footer[data-with-id]').forEach(function (chatForm) {
    var chatWidget = chatForm.closest('.chat-widget');
    var chatBody = chatWidget ? chatWidget.querySelector('.chat-body') : null;
    var chatWith = chatForm.dataset.withId;
    if (!chatBody || !chatWith) return;
    var lastCount = -1;

    function scrollChatToBottom() {
      chatBody.scrollTop = chatBody.scrollHeight;
    }

    function refreshChat() {
      return fetch((window.API_PREFIX || '') + 'api/get_messages.php?with=' + encodeURIComponent(chatWith))
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (!data.success) throw new Error(data.message || 'Could not load messages.');
          if (data.count !== lastCount) {
            chatBody.innerHTML = data.html;
            lastCount = data.count;
            scrollChatToBottom();
          }
          return data.count;
        });
    }

    scrollChatToBottom();
    refreshChat().then(function (count) {
      lastCount = count;
    }).catch(function (error) {
      chatBody.textContent = error.message;
    });

    chatForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = chatForm.querySelector('input[name="message"]');
      var text = input ? input.value.trim() : '';
      if (!text) return;

      fetch((window.API_PREFIX || '') + 'api/send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'receiver_id=' + encodeURIComponent(chatWith) + '&message=' + encodeURIComponent(text)
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            input.value = '';
            refreshChat().catch(function (error) {
              chatBody.textContent = error.message;
            });
          } else {
            alert(data.message || 'Could not send the message.');
          }
        })
        .catch(function () {
          alert('Could not send the message. Please try again.');
        });
    });

    setInterval(function () {
      refreshChat().then(function (count) {
        lastCount = count;
      }).catch(function (error) {
        console.error(error);
      });
    }, 4000);
  });

  /* ---------------- Floating chat launcher popup ---------------- */
  var chatLauncherWrap = document.querySelector('.chat-launcher-wrap');
  var chatLauncher = document.querySelector('.chat-launcher');
  var chatPopup = document.getElementById('chatPopup');

  if (chatLauncherWrap && chatLauncher && chatPopup) {
    var chatClose = chatPopup.querySelector('.chat-close');

    function toggleChatPopup(forceOpen) {
      var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !chatLauncherWrap.classList.contains('open');
      chatLauncherWrap.classList.toggle('open', shouldOpen);
      chatLauncher.setAttribute('aria-expanded', String(shouldOpen));
      chatLauncher.setAttribute('aria-label', shouldOpen ? 'Close chat' : 'Open chat');
      chatPopup.setAttribute('aria-hidden', String(!shouldOpen));
    }

    chatLauncher.addEventListener('click', function (e) {
      e.stopPropagation();
      toggleChatPopup();
    });

    if (chatClose) {
      chatClose.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleChatPopup(false);
      });
    }

    document.addEventListener('click', function (event) {
      if (!chatPopup.contains(event.target) && !chatLauncher.contains(event.target)) {
        toggleChatPopup(false);
      }
    });

    document.querySelectorAll('a[href="#chat"]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        toggleChatPopup(true);
      });
    });

    if (window.location.hash === '#chat') {
      toggleChatPopup(true);
    }
  }

  /* ---------------- Admin chat panel (switch active user) ---------------- */
  document.querySelectorAll('.chat-user-select').forEach(function (item) {
    item.addEventListener('click', function () {
      window.location.href = 'messages.php?with=' + item.dataset.userId;
    });
  });
});
