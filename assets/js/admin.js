(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Create backup
        $('#easy-db-backup-create').on('click', function() {
            var $button = $(this);
            var $status = $('#easy-db-backup-create-status');
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $status.removeClass('success error').addClass('info').html('<span class="easy-db-backup-loading"></span> ' + easyDbBackup.creating_backup).show();
            
            // Send AJAX request
            $.ajax({
                url: easyDbBackup.ajax_url,
                type: 'POST',
                data: {
                    action: 'easy_db_backup_create',
                    nonce: easyDbBackup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.removeClass('info').addClass('success').html(response.data.message);
                        $('#easy-db-backup-list').html(response.data.backups_html);
                        initBackupActions();
                    } else {
                        $status.removeClass('info').addClass('error').html(response.data || easyDbBackup.backup_failed);
                    }
                },
                error: function() {
                    $status.removeClass('info').addClass('error').html(easyDbBackup.backup_failed);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    
                    // Auto-hide success message after 5 seconds
                    if ($status.hasClass('success')) {
                        setTimeout(function() {
                            $status.fadeOut('slow');
                        }, 5000);
                    }
                }
            });
        });
        
        // Initialize backup actions
        function initBackupActions() {
            // Delete backup
            $('.easy-db-backup-delete').off('click').on('click', function() {
                var $button = $(this);
                var $row = $button.closest('tr');
                var filename = $row.data('backup');
                
                if (!confirm(easyDbBackup.confirm_delete)) {
                    return;
                }
                
                // Disable button and show loading
                $button.prop('disabled', true).html('<span class="easy-db-backup-loading"></span> ' + easyDbBackup.deleting);
                
                // Send AJAX request
                $.ajax({
                    url: easyDbBackup.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'easy_db_backup_delete',
                        nonce: easyDbBackup.nonce,
                        filename: filename
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#easy-db-backup-list').html(response.data.backups_html);
                            initBackupActions();
                        } else {
                            alert(response.data || easyDbBackup.delete_failed);
                            $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> ' + easyDbBackup.delete);
                        }
                    },
                    error: function() {
                        alert(easyDbBackup.delete_failed);
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> ' + easyDbBackup.delete);
                    }
                });
            });
        }
        
        // Initialize actions on page load
        initBackupActions();
    });
})(jQuery);