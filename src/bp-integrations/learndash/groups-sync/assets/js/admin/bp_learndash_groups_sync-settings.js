(function($) {
    $(".bp_learndash_groups_sync-scan-groups-button").on('click', function(e) {
        e.preventDefault();

        var $self = $(this);
        var $spinner = $self.next();
        var $results = $(".bp_learndash_groups_sync-scan-results");

        $spinner.addClass('is-active');
        $results.hide().empty();

        $.getJSON($self.data('url'), {
            action: 'bp_learndash_groups_sync/ld-groups-scan',
            _wpnonce: $self.data('nonce'),
        }, function(response) {
            $spinner.removeClass('is-active');
            $results.html(response.data.html).show();
        });
    });

    $('.bp_learndash_groups_sync-scan-results').on('click.sync', '.bp_learndash_groups_sync-do-action-button', function(e) {
        e.preventDefault();

        var $self    = $(this);
        var $row     = $self.closest('tr');
        var $spinner = $self.next();
        var id       = $self.data('id');
        var todo     = $row.find("[name='bp_learndash_groups_sync-ajax-asso-group["+id+"][action]']").val();

        if (todo == 'nothing') {
            $self.trigger('synced');
            return false;
        }

        $spinner.addClass('is-active');
        $self.attr('disabled', true);

        $.getJSON($self.data('url'), {
            action: 'bp_learndash_groups_sync/ld-group-sync',
            _wpnonce: $self.data('nonce'),
            id: id,
            todo: todo
        }, function(response) {
            $self.trigger('synced');
            $spinner.removeClass('is-active');
            $row.html($(response.data.html).html());
        });
    });

    $('.bp_learndash_groups_sync-scan-results').on('click', '.bp_learndash_groups_sync-bulk-action-button', function(e) {
        e.preventDefault();

        var $self = $(this);
        var $spinner = $self.next();

        if (! $self.data('start-text')) {
            $self.data('start-text', $self.text());
        }

        if ($self.data('processing')) {
            $self.trigger('queue_end');
            return;
        }

        $spinner.addClass('is-active');
        $self.text($self.data('stop-text'));
        $self.data('processing', true);
        $self.data('queue', $('.bp_learndash_groups_sync-scan-results .bp_learndash_groups_sync-do-action-button').toArray());

        $self.trigger('queue_tick');
    });

    $('.bp_learndash_groups_sync-scan-results').on('queue_end', '.bp_learndash_groups_sync-bulk-action-button', function() {
        var $self = $(this);
        var $spinner = $self.next();

        $self.data('queue', []);
        $self.data('processing', false);
        $self.text($self.data('start-text'));
        $spinner.removeClass('is-active');
    });

    $('.bp_learndash_groups_sync-scan-results').on('queue_tick', '.bp_learndash_groups_sync-bulk-action-button', function() {
        var $self = $(this);

        if (! $self.data('queue').length) {
            $self.trigger('queue_end');
            return;
        }

        var $next = $($self.data('queue').shift());

        $next.one('synced', function() {
            $self.trigger('queue_tick');
        });

        $next.trigger('click.sync');
    });
})(jQuery);
