<style>
    .ba-doc { font-size: 14px; line-height: 1.55; color: inherit; max-width: 760px; margin: 0 auto; }
    .ba-head { display: flex; align-items: center; gap: 14px; padding-bottom: 12px; border-bottom: 2px solid currentColor; margin-bottom: 18px; }
    .ba-logo { height: 44px; width: auto; }
    .ba-company { font-weight: 700; font-size: 16px; }
    .ba-title { text-align: center; font-size: 18px; font-weight: 700; letter-spacing: .04em; margin: 8px 0 2px; }
    .ba-center { text-align: center; margin-top: 0; }
    .ba-doc h2 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 6px; opacity: .8; }
    .ba-doc p { margin: 10px 0; }
    .ba-muted { opacity: .65; }
    .ba-small { font-size: 12px; }
    .ba-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; }
    .ba-right { text-align: right; }
    .ba-kv, .ba-grid { width: 100%; border-collapse: collapse; }
    .ba-kv th { text-align: left; font-weight: 500; opacity: .7; width: 34%; padding: 4px 8px 4px 0; vertical-align: top; }
    .ba-kv td { padding: 4px 0; }
    .ba-grid th { text-align: left; font-weight: 600; font-size: 12px; padding: 6px 8px; border-bottom: 1px solid rgba(127,127,127,.4); }
    .ba-grid td { padding: 6px 8px; border-bottom: 1px solid rgba(127,127,127,.18); }
    .ba-terms { font-size: 13px; padding: 10px 12px; border: 1px solid rgba(127,127,127,.3); border-radius: 8px; }
    .ba-signs { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 26px; }
    .ba-sign { text-align: center; }
    .ba-sign-box { height: 110px; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid currentColor; margin: 6px 0; }
    .ba-sign-box img { max-height: 104px; max-width: 100%; }
    .ba-sign-name { font-weight: 600; }
    .sig-pad { width: 100%; height: 180px; touch-action: none; border-radius: 10px; background: #fff; border: 1px dashed #94a3b8; cursor: crosshair; }
    .sig-bar { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 6px; font-size: 12px; }
    .sig-clear { font: inherit; padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(127,127,127,.4); background: transparent; color: inherit; cursor: pointer; }
    .sig-hint { opacity: .7; }
    .ba-scroll { overflow-x: auto; }
    @media (max-width: 480px) { .ba-kv th { width: 40%; } .ba-signs { gap: 14px; } .ba-doc { font-size: 13px; } }
    .ba-closing { break-inside: avoid; page-break-inside: avoid; }

    /* Cetak: satu lembar A4. Kop cetak bawaan aplikasi & judul halaman tidak dipakai (BA punya kop
       sendiri), dan kartu pembungkus boleh terpotong — aturan umum "kartu tidak boleh terpotong"
       dulu mendorong seluruh BA ke lembar kedua sehingga lembar pertama kosong. */
    @page { size: A4; margin: 12mm 14mm; }
    @media print {
        .no-print, body:has(.ba-doc) .print-letterhead, body:has(.ba-doc) .page-header { display: none !important; }
        html, body { background: #fff !important; color: #0f172a !important; }
        .ba-sheet { break-inside: auto !important; border: 0 !important; padding: 0 !important; margin: 0 !important; box-shadow: none !important; background: none !important; }
        .ba-sheet > .card-body { padding: 0 !important; }
        .ba-doc { max-width: none; font-size: 11.5px; line-height: 1.4; color: #0f172a; }
        .ba-head { padding-bottom: 8px; margin-bottom: 10px; }
        .ba-logo { height: 36px; }
        .ba-company { font-size: 14px; }
        .ba-title { font-size: 15px; margin-top: 4px; }
        .ba-doc h2 { font-size: 10.5px; margin: 10px 0 3px; }
        .ba-doc p { margin: 6px 0; }
        .ba-kv th, .ba-kv td { padding-top: 1.5px; padding-bottom: 1.5px; }
        .ba-grid th, .ba-grid td { padding: 3px 6px; }
        .ba-mono { font-size: 10.5px; }
        .ba-terms { font-size: 10px; padding: 6px 8px; line-height: 1.35; }
        .ba-signs { margin-top: 12px; }
        .ba-sign-box { height: 80px; }
        .ba-sign-box img { max-height: 76px; }
        tr { break-inside: avoid; }
    }
</style>
