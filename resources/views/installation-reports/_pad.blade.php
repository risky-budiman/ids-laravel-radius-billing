{{-- Kanvas tanda tangan (mouse / jari / stylus) -> input tersembunyi "signature" berisi PNG data-URL.
     JavaScript biasa (tanpa Alpine) agar bisa dipakai di halaman staf maupun halaman publik pelanggan. --}}
@php $padId = 'pad_' . $role; @endphp
<div class="sig-wrap" data-sig-pad="{{ $padId }}">
    <canvas id="{{ $padId }}" class="sig-pad" aria-label="Area tanda tangan"></canvas>
    <input type="hidden" name="signature" data-sig-out>
    <div class="sig-bar">
        <button type="button" class="sig-clear" data-sig-clear>Ulangi</button>
        <span class="sig-hint" data-sig-hint>Tanda tangan di dalam kotak</span>
    </div>
</div>
@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-sig-pad]').forEach(function (wrap) {
                var c = wrap.querySelector('canvas'), out = wrap.querySelector('[data-sig-out]'), hint = wrap.querySelector('[data-sig-hint]');
                var ctx, drawing = false, empty = true;
                function setEmpty(v) { empty = v; hint.textContent = v ? 'Tanda tangan di dalam kotak' : 'Siap disimpan'; if (v) out.value = ''; }
                function fit() {
                    var r = c.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
                    c.width = r.width * dpr; c.height = r.height * dpr;
                    ctx = c.getContext('2d'); ctx.scale(dpr, dpr);
                    ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0f172a';
                    setEmpty(true);
                }
                function pos(e) { var r = c.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; }
                c.addEventListener('pointerdown', function (e) { drawing = true; c.setPointerCapture(e.pointerId); var p = pos(e); ctx.beginPath(); ctx.moveTo(p[0], p[1]); ctx.lineTo(p[0] + .1, p[1] + .1); ctx.stroke(); });
                c.addEventListener('pointermove', function (e) { if (!drawing) return; var p = pos(e); ctx.lineTo(p[0], p[1]); ctx.stroke(); setEmpty(false); });
                function end() { if (!drawing) return; drawing = false; if (!empty) out.value = c.toDataURL('image/png'); }
                c.addEventListener('pointerup', end); c.addEventListener('pointercancel', end);
                wrap.querySelector('[data-sig-clear]').addEventListener('click', fit);
                var form = wrap.closest('form');
                if (form) form.addEventListener('submit', function (e) {
                    if (!out.value) { e.preventDefault(); hint.textContent = 'Tanda tangan masih kosong'; hint.style.color = '#e11d48'; }
                });
                fit();
            });
        });
    </script>
@endonce
