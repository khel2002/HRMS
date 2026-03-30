/**
 * user-management.js
 * Full CRUD for Account Management — jQuery AJAX + Bootstrap 5 + SweetAlert2
 */

'use strict';

// ── CSRF setup ────────────────────────────────────────────────────────────────

$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

// ── Constants ─────────────────────────────────────────────────────────────────

const BASE_URL = '/admin/account-management';

// ── Utility helpers ───────────────────────────────────────────────────────────

function userUrl(id) {
  return BASE_URL + '/' + id;
}
function statusUrl(id) {
  return BASE_URL + '/' + id + '/change-status';
}

function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function statusBadge(status) {
  var classes = { active: 'bg-success', inactive: 'bg-danger', suspended: 'bg-warning text-dark' };
  var cls = classes[status] || 'bg-secondary';
  return '<span class="badge ' + cls + '">' + capitalize(status) + '</span>';
}

function escHtml(str) {
  return String(str == null ? '' : str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function escJs(str) {
  return String(str == null ? '' : str)
    .replace(/\\/g, '\\\\')
    .replace(/'/g, "\\'");
}

function clearErrors($form) {
  $form.find('.is-invalid').removeClass('is-invalid');
  $form.find('.invalid-feedback').text('');
}

function showErrors(errors, prefix) {
  prefix = prefix || '';
  $.each(errors, function (field, msgs) {
    var $input = $('#' + prefix + field);
    $input.addClass('is-invalid');
    $('#' + prefix + 'err_' + field).text(msgs[0]);
  });
}

function btnLoading($btn, original) {
  if (original === undefined) {
    // save and set loading
    $btn.data('original-html', $btn.html());
    $btn
      .prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Processing...');
  } else {
    // restore
    $btn.prop('disabled', false).html($btn.data('original-html') || original);
  }
}

// ── Modal helpers ────────────────────────────────────────────────────────────

function openModal(id) {
  var el = document.getElementById(id);
  // Dispose any stale instance left from a previous open/close cycle,
  // then create a clean one. This is the only pattern that works reliably
  // for repeated open/close in Bootstrap 5.
  var stale = bootstrap.Modal.getInstance(el);
  if (stale) stale.dispose();
  new bootstrap.Modal(el).show();
}

function closeModal(id) {
  var el = document.getElementById(id);
  var instance = bootstrap.Modal.getInstance(el);
  if (instance) {
    instance.hide();
    // Dispose after hide so the element is clean for the next openModal call
    $(el).one('hidden.bs.modal', function () {
      var i = bootstrap.Modal.getInstance(el);
      if (i) i.dispose();
    });
  }
}

// ── Init existing rows on page load ──────────────────────────────────────────

$(function () {
  $('table tbody tr').each(function () {
    var onclick = $(this).find('[title="View Details"]').attr('onclick') || '';
    var match = onclick.match(/viewUser\((\d+)/);
    if (match) $(this).attr('data-user-id', match[1]);
  });
});

// ── VIEW ─────────────────────────────────────────────────────────────────────

function viewUser(userId) {
  // Reset modal content to loading state first
  $('#viewUserContent').hide();
  $('#viewUserError').hide();
  $('#viewUserLoading').show();

  openModal('viewUserModal');

  $.ajax({
    url: userUrl(userId),
    method: 'GET',
    dataType: 'json',
    success: function (res) {
      if (!res.success) {
        $('#viewUserLoading').hide();
        $('#viewUserError').show();
        return;
      }

      var u = res.user;
      $('#viewFullName').text(u.full_name || '—');
      $('#viewUsername').text(u.username || '—');
      $('#viewEmail').text(u.email || '—');
      $('#viewRole').text(u.role || '—');
      $('#viewOffice').text(u.office || '—');
      $('#viewStatus').html(statusBadge(u.status));
      $('#viewCreatedAt').text(u.created_at || '—');
      $('#viewUpdatedAt').text(u.updated_at || '—');

      $('#viewUserLoading').hide();
      $('#viewUserContent').show();
    },
    error: function () {
      $('#viewUserLoading').hide();
      $('#viewUserError').show();
    }
  });
}

// ── ADD ───────────────────────────────────────────────────────────────────────

$('#addUserModal').on('hidden.bs.modal', function () {
  document.getElementById('addUserForm').reset();
  clearErrors($('#addUserForm'));
});

$('#addUserForm').on('submit', function (e) {
  e.preventDefault();

  var $form = $(this);
  var $btn = $('#addUserBtn');
  clearErrors($form);
  btnLoading($btn);

  $.ajax({
    url: BASE_URL,
    method: 'POST',
    data: $form.serialize(),
    dataType: 'json',
    success: function (res) {
      btnLoading($btn, 'restore');
      if (!res.success) return;

      closeModal('addUserModal');
      prependRow(res.user);

      // Remove the newly-used employee from the dropdown
      $("#employee_id option[value='" + res.user.employee_id + "']").remove();

      Swal.fire({ icon: 'success', title: 'User Created', text: res.message, timer: 2000, showConfirmButton: false });
    },
    error: function (xhr) {
      btnLoading($btn, 'restore');
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        showErrors(xhr.responseJSON.errors);
      } else {
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
      }
    }
  });
});

// ── EDIT ──────────────────────────────────────────────────────────────────────

/**
 * Open the edit modal and populate it by fetching fresh data from the server.
 * This guarantees the form always reflects the latest saved values.
 */
function editUser(userId) {
  // Reset and open modal immediately — show spinner while loading
  var $form = $('#editUserForm');
  $form[0].reset();
  clearErrors($form);
  $('#editUserBtn').prop('disabled', true);
  openModal('editUserModal');

  $.ajax({
    url: userUrl(userId),
    method: 'GET',
    dataType: 'json',
    success: function (res) {
      if (!res.success) {
        closeModal('editUserModal');
        Swal.fire('Error', 'Could not load user data. Please try again.', 'error');
        return;
      }
      var u = res.user;
      $('#editUserId').val(u.id);
      $('#editRoleId').val(u.role_id);
      $('#editUsername').val(u.username);
      $('#editPassword').val('');
      $('#editUserBtn').prop('disabled', false);
    },
    error: function () {
      closeModal('editUserModal');
      Swal.fire('Error', 'Could not load user data. Please try again.', 'error');
    }
  });
}

$('#editUserModal').on('hidden.bs.modal', function () {
  var $form = $('#editUserForm');
  $form[0].reset();
  clearErrors($form);
  $('#editUserBtn').prop('disabled', false);
});

$('#editUserForm').on('submit', function (e) {
  e.preventDefault();

  var $form = $(this);
  var $btn = $('#editUserBtn');
  var userId = $('#editUserId').val();
  clearErrors($form);
  btnLoading($btn);

  $.ajax({
    url: userUrl(userId),
    method: 'POST',
    data: $form.serialize() + '&_method=PUT',
    dataType: 'json',
    success: function (res) {
      btnLoading($btn, 'restore');
      if (!res.success) return;
      closeModal('editUserModal');
      replaceRow(res.user);
      Swal.fire({ icon: 'success', title: 'User Updated', text: res.message, timer: 2000, showConfirmButton: false });
    },
    error: function (xhr) {
      btnLoading($btn, 'restore');
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        showErrors(xhr.responseJSON.errors, 'edit_');
      } else {
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
      }
    }
  });
});

// ── CHANGE STATUS ─────────────────────────────────────────────────────────────

function changeStatus(userId, fullName, currentStatus) {
  $('#statusUserId').val(userId);
  $('#statusUserName').text(fullName);
  $('#newStatus').val(currentStatus.toLowerCase());
  openModal('changeStatusModal');
}

$('#changeStatusModal').on('hidden.bs.modal', function () {
  $('#newStatus').val('');
});

$('#changeStatusForm').on('submit', function (e) {
  e.preventDefault();

  var userId = $('#statusUserId').val();
  var $btn = $('#changeStatusBtn');
  btnLoading($btn);

  $.ajax({
    url: statusUrl(userId),
    method: 'POST',
    data: $(this).serialize() + '&_method=PATCH',
    dataType: 'json',
    success: function (res) {
      btnLoading($btn, 'restore');
      if (!res.success) return;

      closeModal('changeStatusModal');

      // Update the badge directly in the row
      $('tr[data-user-id="' + userId + '"] .user-status-badge').html(statusBadge(res.status));

      Swal.fire({ icon: 'success', title: 'Status Updated', text: res.message, timer: 2000, showConfirmButton: false });
    },
    error: function () {
      btnLoading($btn, 'restore');
      Swal.fire('Error', 'Could not update status. Please try again.', 'error');
    }
  });
});

// ── DELETE ────────────────────────────────────────────────────────────────────

function deleteUser(userId, fullName) {
  Swal.fire({
    title: 'Delete "' + fullName + '"?',
    text: 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    confirmButtonColor: '#dc3545',
    cancelButtonText: 'Cancel'
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: userUrl(userId),
      method: 'POST',
      data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
      dataType: 'json',
      success: function (res) {
        if (!res.success) return;
        $('tr[data-user-id="' + userId + '"]').fadeOut(300, function () {
          $(this).remove();
          if ($('table tbody tr').length === 0) {
            $('table tbody').html(
              '<tr><td colspan="5" class="text-center text-muted py-4">No user accounts found.</td></tr>'
            );
          }
        });
        Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 2000, showConfirmButton: false });
      },
      error: function () {
        Swal.fire('Error', 'Could not delete the user. Please try again.', 'error');
      }
    });
  });
}

// ── TABLE ROW BUILDERS ────────────────────────────────────────────────────────

function buildRow(u) {
  return (
    '<tr data-user-id="' +
    u.id +
    '">' +
    '<td>' +
    escHtml(u.office) +
    '</td>' +
    '<td>' +
    escHtml(u.username) +
    '</td>' +
    '<td class="text-center"><span class="user-status-badge">' +
    statusBadge(u.status) +
    '</span></td>' +
    '<td class="text-center">' +
    escHtml(u.created_at) +
    '</td>' +
    '<td class="text-center">' +
    '<div class="d-flex justify-content-center gap-1">' +
    '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button" title="View Details" ' +
    'onclick="viewUser(' +
    u.id +
    ')">' +
    '<i class="icon-base ri ri-eye-line"></i>' +
    '</button>' +
    '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button" title="Edit" ' +
    'onclick="editUser(' +
    u.id +
    ')">' +
    '<i class="icon-base ri ri-edit-line"></i>' +
    '</button>' +
    '<div class="dropdown">' +
    '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button" ' +
    'data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">' +
    '<i class="icon-base ri ri-more-2-line"></i>' +
    '</button>' +
    '<div class="dropdown-menu dropdown-menu-end">' +
    '<a class="dropdown-item" href="javascript:void(0);" ' +
    'onclick="changeStatus(' +
    u.id +
    ", '" +
    escJs(u.full_name) +
    "', '" +
    escJs(u.status) +
    '\')">' +
    '<i class="icon-base ri ri-refresh-line me-2"></i>Change Status' +
    '</a>' +
    '<div class="dropdown-divider"></div>' +
    '<a class="dropdown-item text-danger" href="javascript:void(0);" ' +
    'onclick="deleteUser(' +
    u.id +
    ", '" +
    escJs(u.full_name) +
    '\')">' +
    '<i class="icon-base ri ri-delete-bin-line me-2"></i>Delete Account' +
    '</a>' +
    '</div>' +
    '</div>' +
    '</div>' +
    '</td>' +
    '</tr>'
  );
}

function prependRow(u) {
  $('table tbody').prepend(buildRow(u));
}

function replaceRow(u) {
  var $row = $('tr[data-user-id="' + u.id + '"]');
  if ($row.length) $row.replaceWith(buildRow(u));
}

// ── SEARCH & FILTER ───────────────────────────────────────────────────────────

function filterTable() {
  var search = $('#searchTable').val().toLowerCase();
  var status = $('#filterStatus').val().toLowerCase();

  $('table tbody tr[data-user-id]').each(function () {
    var text = $(this).text().toLowerCase();
    var badge = $(this).find('.badge').text().toLowerCase();
    var matchS = !search || text.includes(search);
    var matchF = !status || badge.includes(status);
    $(this).toggle(matchS && matchF);
  });
}

$('#searchTable').on('input', filterTable);
$('#filterStatus').on('change', filterTable);
