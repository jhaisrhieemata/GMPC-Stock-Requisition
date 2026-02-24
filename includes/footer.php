    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);

            // Confirm delete
            window.confirmDelete = function(message) {
                return confirm(message || 'Are you sure you want to delete this item?');
            };

            // Select All functionality
            $('#selectAll, [id="selectAll"]').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('.item-checkbox').prop('checked', isChecked);
            });

            // Item checkbox change
            $('.item-checkbox').on('change', function() {
                var totalCheckboxes = $('.item-checkbox').length;
                var checkedCheckboxes = $('.item-checkbox:checked').length;
                $('#selectAll, [id="selectAll"]').prop('checked', totalCheckboxes === checkedCheckboxes);
            });

            // Smooth scroll to top on page load
            $('html, body').animate({ scrollTop: 0 }, 'fast');

            // Add loading state to forms
            $('form').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
            });

            // Smooth table row hover effect
            $('tbody tr').hover(
                function() { $(this).addClass('table-active'); },
                function() { $(this).removeClass('table-active'); }
            );

            // Search input debounce
            let searchTimeout;
            $('input[name="search"]').on('keyup', function() {
                clearTimeout(searchTimeout);
                var value = $(this).val();
                searchTimeout = setTimeout(function() {
                    // Auto-submit after 500ms of typing (optional)
                    // $(this).closest('form').submit();
                }, 500);
            });

            // Initialize all tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize all popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            // Modal backdrop fix for multiple modals
            $(document).on('show.bs.modal', '.modal', function() {
                var zIndex = Math.max.apply(null, Array.from(document.querySelectorAll('.modal')).map(function(el) {
                    return parseInt(window.getComputedStyle(el).zIndex) || 0;
                }));
                $(this).css('z-index', zIndex + 10);
                setTimeout(function() {
                    $('.modal-backdrop').not('.in').addClass('show');
                }, 0);
            });

            // Fix modal close
            $(document).on('hidden.bs.modal', '.modal', function() {
                if ($('.modal.show').length > 0) {
                    $('body').addClass('modal-open');
                }
            });

            // Add fade-in effect to cards
            $('.card').each(function(i) {
                $(this).css({
                    'opacity': 0,
                    'transform': 'translateY(20px)'
                }).animate({
                    'opacity': 1,
                    'transform': 'translateY(0)'
                }, 300 + (i * 50), 'swing');
            });

            // Table responsive wrapper
            $('.table-responsive').on('scroll', function() {
                $(this).toggleClass('shadow-sm', this.scrollWidth > this.clientWidth);
            });

            // Sidebar toggle for mobile
            $('.mobile-menu-toggle, .sidebar-overlay').on('click', function() {
                $('.sidebar').toggleClass('show');
                $('.sidebar-overlay').toggleClass('show');
            });

            // Close sidebar on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.sidebar').removeClass('show');
                    $('.sidebar-overlay').removeClass('show');
                }
            });

            // Print button handler
            $('[onclick="window.print()"]').on('click', function() {
                setTimeout(function() {
                    window.print();
                }, 100);
            });

            // Handle print media query
            if (window.matchMedia('print').matches) {
                $('.sidebar, .mobile-menu-toggle, .btn, .no-print').hide();
                $('.main-content').css('margin-left', '0');
            }
        });

        // Global error handler
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.log('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
            return false;
        };

        // Loading overlay functions
        window.showLoading = function() {
            $('body').append('<div id="loadingOverlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        };

        window.hideLoading = function() {
            $('#loadingOverlay').fadeOut(function() { $(this).remove(); });
        };

        // Show loading on form submit
        $(document).on('submit', 'form', function() {
            if ($(this).find('button[type="submit"]').length) {
                showLoading();
            }
        });
    </script>
</body>
</html>
