@if (session('success') || session('error'))
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'نجاح', text: @json(session('success')), confirmButtonText: 'ممتاز' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'خطأ', text: @json(session('error')), confirmButtonText: 'حسنًا' });
        @endif
    });
</script>
@endif

