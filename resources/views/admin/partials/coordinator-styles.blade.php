@once
<style>
.coord-shell{display:grid;gap:18px;max-width:1240px}
.coord-grid{display:grid;gap:14px}
.coord-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.coord-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.coord-card{background:#fff;border:1px solid rgba(107,47,160,.11);border-radius:16px;box-shadow:0 10px 28px rgba(57,26,101,.06);overflow:hidden}
.coord-card-pad{padding:20px}
.coord-stat{display:grid;gap:9px;min-height:128px;padding:20px;background:#fff;border:1px solid rgba(107,47,160,.11);border-radius:16px;box-shadow:0 10px 24px rgba(57,26,101,.05)}
.coord-stat span,.coord-eyebrow,.coord-field label{color:#7251a0;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}
.coord-stat strong{color:#1f1235;font-size:32px;line-height:1;font-weight:900}
.coord-stat small{color:#716280;font-size:12.5px;line-height:1.4}
.coord-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;background:#fbf9ff;border-bottom:1px solid #eee6fa}
.coord-head h2,.coord-head h3{margin:0;color:#201044;font-size:18px;line-height:1.25}
.coord-head p{margin:5px 0 0;color:#76668c;font-size:13px;line-height:1.45}
.coord-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.coord-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 14px;border-radius:10px;border:1px solid transparent;font:800 13px/1 inherit;text-decoration:none;cursor:pointer;white-space:nowrap}
.coord-btn-primary{background:#4c1d95;color:#fff;box-shadow:0 10px 20px rgba(76,29,149,.18)}
.coord-btn-soft{background:#f6f1fc;color:#5b248c;border-color:#e3d7f4}
.coord-btn-danger{background:#fee2e2;color:#991b1b;border-color:#fecaca}
.coord-btn-success{background:#dcfce7;color:#166534;border-color:#bbf7d0}
.coord-filter{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;align-items:end;padding:16px}
.coord-field{display:grid;gap:7px;min-width:0}
.coord-field input,.coord-field select,.coord-field textarea{width:100%;min-height:42px;border:1.5px solid #e6daf7;border-radius:10px;background:#fff;color:#1f1235;font:inherit;padding:9px 12px;box-sizing:border-box}
.coord-field textarea{min-height:140px;line-height:1.6;resize:vertical}
.coord-table-wrap{overflow-x:auto}
.coord-table{width:100%;border-collapse:collapse;font-size:13px}
.coord-table th{padding:12px 15px;background:#fbf9ff;color:#5f418f;font-size:10.5px;font-weight:900;letter-spacing:.08em;text-align:left;text-transform:uppercase;border-bottom:1px solid #eee6fa;white-space:nowrap}
.coord-table td{padding:14px 15px;border-bottom:1px solid #f2edf9;color:#24113f;vertical-align:top}
.coord-table tr:last-child td{border-bottom:none}
.coord-title{display:block;color:#321061;font-weight:900;line-height:1.35;text-decoration:none}
.coord-muted{display:block;margin-top:4px;color:#76668c;font-size:12px;line-height:1.4}
.coord-pill{display:inline-flex;align-items:center;min-height:26px;padding:0 10px;border-radius:999px;font-size:11.5px;font-weight:900;background:#f2edf9;color:#5b248c;border:1px solid #e3d7f4;white-space:nowrap}
.coord-pill-draft{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.coord-pill-pending{background:#fef3c7;color:#92400e;border-color:#fde68a}
.coord-pill-approved{background:#dcfce7;color:#166534;border-color:#bbf7d0}
.coord-pill-rejected{background:#fee2e2;color:#991b1b;border-color:#fecaca}
.coord-pill-archived{background:#e5e7eb;color:#374151;border-color:#d1d5db}
.coord-pill-received{background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe}
.coord-empty{padding:38px 20px;text-align:center;color:#76668c}
.coord-empty strong{display:block;color:#201044;margin-bottom:5px}
.coord-pagination{padding:14px 16px;border-top:1px solid #f2edf9}
.coord-list{display:grid;gap:12px;padding:16px}
.coord-item{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px;border:1px solid #eee6fa;border-radius:12px;background:#fff}
.coord-item-main{min-width:0}
.coord-item-main h3{margin:0;color:#201044;font-size:15px;line-height:1.35}
.coord-item-main p{margin:5px 0 0;color:#75648d;font-size:12.5px;line-height:1.45}
.coord-meter{height:10px;border-radius:999px;background:#f0e8f8;overflow:hidden}
.coord-meter span{display:block;height:100%;background:#6d28d9;border-radius:inherit}
@media(max-width:1120px){.coord-grid-4,.coord-grid-3{grid-template-columns:repeat(2,minmax(0,1fr))}.coord-filter{grid-template-columns:repeat(2,minmax(0,1fr))}.coord-filter .coord-actions{grid-column:1/-1}}
@media(max-width:720px){.coord-grid-4,.coord-grid-3,.coord-filter{grid-template-columns:1fr}.coord-head,.coord-item{align-items:stretch;flex-direction:column}.coord-actions .coord-btn{width:100%}.coord-table{min-width:760px}}
</style>
@endonce
