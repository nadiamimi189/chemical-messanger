/**
 * Chemical Connect - front-end behaviour
 * Handles: AJAX comment posting, chat send/poll, like toggle,
 * media file preview, admin thread switching.
 */

document.addEventListener('DOMContentLoaded', function () {

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
