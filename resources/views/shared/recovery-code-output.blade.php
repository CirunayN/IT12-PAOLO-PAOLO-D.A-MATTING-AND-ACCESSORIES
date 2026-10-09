<script>
function downloadRecoveryCodes(codes) {
    const form = document.createElement('form');
    form.method = 'POST'; form.action = @json(route('recovery-codes.download'));
    const token = document.createElement('input'); token.type = 'hidden'; token.name = '_token'; token.value = @json(csrf_token()); form.appendChild(token);
    codes.forEach(code => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'codes[]'; input.value = code; form.appendChild(input); });
    document.body.appendChild(form); form.submit(); form.remove();
}
</script>