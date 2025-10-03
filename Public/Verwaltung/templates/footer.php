    </main>
    
    <!-- jQuery -->
    <script src="/assets/jquery/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="/assets/dataTables/js/jquery.dataTables.min.js"></script>
    <script src="/assets/dataTables/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="/assets/sweetalert2/sweetalert2.all.min.js"></script>
    
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('.datatable').DataTable({
                language: {
                    url: '<?php echo $ADMIN_ROOT; ?>/assets/js/datatables-de.json'
                },
                responsive: true,
                order: [],
                ordering: true
            });
            
            // Confirm delete actions
            $('.delete-confirm').on('click', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                
                Swal.fire({
                    title: 'Sind Sie sicher?',
                    text: "Diese Aktion kann nicht rückgängig gemacht werden!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#a72920',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ja, löschen!',
                    cancelButtonText: 'Abbrechen'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html> 