import { writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

// Goal-level use cases verified against current routes, controllers and model checks.
const groups = [
  { actor: 'Guest / Public visitor', side: 'left', row: 0, title: 'Public repository', cases: [
    ['browse', 'Search and filter', 'published research'],
    ['details', 'View research details', 'and copy citations'],
    ['information', 'Read About, FAQ and policy'],
    ['contact', 'Send a contact message'],
  ] },
  { actor: 'Account holder', side: 'right', row: 0, title: 'Shared account access', cases: [
    ['login', 'Log in / Log out'],
    ['reset', 'Reset forgotten password'],
    ['policy', 'Accept repository policy'],
    ['profile', 'Update profile, photo', 'and password'],
  ] },
  { actor: 'Student / Faculty', side: 'left', row: 1, title: 'Member access', cases: [
    ['reader_dashboard', 'View personal dashboard', 'and recently viewed research'],
    ['read', 'Read full documents', 'in the protected viewer'],
    ['pin', 'Save / Unsave research', 'and view saved items'],
  ] },
  { actor: 'Research Coordinator', side: 'right', row: 1, title: 'Journal preparation', cases: [
    ['tasks', 'View assigned journal tasks', 'and batch status'],
    ['prepare', 'Add / Edit journal details', 'and upload completed PDFs'],
    ['submit', 'Submit completed batch', 'to the Dean'],
    ['coordinator_reports', 'Generate preparation reports', 'and export PDF / Excel'],
    ['coordinator_notifications', 'View workflow notifications'],
  ] },
  { actor: 'Department Dean', side: 'left', row: 2, title: 'Department management and review', cases: [
    ['assign', 'Assign journal task', 'by Academic Year and program'],
    ['forward', 'Review submitted journal PDFs', 'and forward batch to Admin'],
    ['import', 'Create / Import', 'Student and Faculty accounts'],
    ['activate', 'Activate continuing members', 'for the current semester'],
    ['department_users', 'Manage department members', 'and view user activity logs'],
    ['dean_reports', 'View dashboard, notifications', 'and department reports'],
  ] },
  { actor: 'Administrator', side: 'right', row: 2, title: 'System administration and publication', cases: [
    ['publish', 'Review forwarded journal PDFs', 'and publish the entire batch'],
    ['staff', 'Create Dean and', 'Coordinator accounts'],
    ['semesters', 'Manage Academic Years', 'and semesters'],
    ['admin_users', 'Manage user accounts'],
    ['admin_reports', 'View dashboard, notifications', 'and export repository reports'],
    ['logs', 'Inspect system activity', 'and capture-attempt logs'],
    ['messages', 'Manage contact messages'],
  ] },
];

const out = name => fileURLToPath(new URL(name, import.meta.url));
const escape = text => text.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
const svg = [
  '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="1640" viewBox="0 0 1600 1640" role="img" aria-labelledby="title description">',
  '<title id="title">UBE Research Repository — Updated Use Case Diagram</title>',
  '<desc id="description">UML actors connected to goal-level use cases inside the repository boundary. Shared account and public capabilities apply to every relevant role. Current journal workflow: Dean assigns, Coordinator prepares and submits, Dean forwards, Administrator publishes.</desc>',
  '<style>text{font-family:Arial,sans-serif;fill:#262033}.heading{font-size:30px;font-weight:700}.sub{font-size:17px;fill:#625772}.group{font-size:18px;font-weight:700;fill:#584477}.label{font-size:17px}.actor{font-size:18px;font-weight:700}.note{font-size:17px;fill:#51485f}.association{stroke:#80728e;stroke-width:1.6;fill:none}.stick{stroke:#49365f;stroke-width:2.5;fill:none}</style>',
  '<rect width="1600" height="1640" fill="#fff"/>',
  '<text x="800" y="48" text-anchor="middle" class="heading">UBE Research Repository</text>',
  '<text x="800" y="79" text-anchor="middle" class="sub">Updated use case diagram · Current implementation · 13 October 2026</text>',
  '<rect x="210" y="108" width="1180" height="1320" rx="10" fill="#fcfaff" stroke="#5c427a" stroke-width="2"/>',
  '<text x="800" y="141" text-anchor="middle" class="group">System boundary</text>',
];

for (const group of groups) {
  const left = group.side === 'left';
  const panelX = left ? 240 : 835;
  const panelY = 165 + group.row * 410;
  const panelW = 525;
  const centerX = panelX + panelW / 2;
  const actorX = left ? 104 : 1496;
  const actorY = panelY + 180;
  svg.push(`<rect x="${panelX}" y="${panelY}" width="${panelW}" height="390" rx="8" fill="#f5f0fa" stroke="#e3d9ed"/>`);
  svg.push(`<text x="${centerX}" y="${panelY + 29}" text-anchor="middle" class="group">${escape(group.title)}</text>`);
  const spacing = 330 / group.cases.length;
  const ry = Math.min(29, spacing / 2 - 4);
  const edgeX = left ? centerX - 225 : centerX + 225;
  for (const [index, [id, ...lines]] of group.cases.entries()) {
    const cy = panelY + 52 + spacing * (index + 0.5);
    svg.push(`<path class="association" d="M ${actorX + (left ? 23 : -23)} ${actorY} L ${left ? panelX - 14 : panelX + panelW + 14} ${actorY} L ${edgeX} ${cy}"/>`);
    svg.push(`<ellipse id="${id}" cx="${centerX}" cy="${cy}" rx="225" ry="${ry}" fill="white" stroke="#6b4d8c" stroke-width="1.5"/>`);
    svg.push(`<text x="${centerX}" y="${cy - (lines.length - 1) * 10 + 6}" text-anchor="middle" class="label">${lines.map((line, i) => `<tspan x="${centerX}" dy="${i ? 20 : 0}">${escape(line)}</tspan>`).join('')}</text>`);
  }
  svg.push(`<circle class="stick" cx="${actorX}" cy="${actorY - 49}" r="15"/><path class="stick" d="M ${actorX} ${actorY - 34} V ${actorY + 14} M ${actorX - 27} ${actorY - 16} H ${actorX + 27} M ${actorX} ${actorY + 14} L ${actorX - 23} ${actorY + 49} M ${actorX} ${actorY + 14} L ${actorX + 23} ${actorY + 49}"/>`);
  const actorLines = group.actor.split(' / ').length > 1 ? group.actor.split(' / ') : group.actor === 'Research Coordinator' ? ['Research', 'Coordinator'] : group.actor === 'Department Dean' ? ['Department', 'Dean'] : [group.actor];
  svg.push(`<text x="${actorX}" y="${actorY + 77}" text-anchor="middle" class="actor">${actorLines.map((line, i) => `<tspan x="${actorX}" dy="${i ? 23 : 0}">${escape(line)}</tspan>`).join('')}</text>`);
}
svg.push('<text x="800" y="1406" text-anchor="middle" class="sub">Solid lines represent actor participation; they do not represent workflow order.</text>');
const notes = [
  'Account holder represents Student, Faculty, Dean, Coordinator and Administrator accounts.',
  'All roles can use the public repository; shared account actions apply to all account holders.',
  'Member full-document access requires an active, approved account, current policy acceptance and active semester enrollment.',
  'Dean and Coordinator actions are restricted to their department and assigned journal tasks.',
  'Journals become read-only after submission. Submit, forward and publish require complete journal details and every PDF.',
  'Current flow has no separate Dean approval or return step. Student and Faculty direct submission is disabled.',
];
notes.forEach((note, i) => svg.push(`<text x="52" y="1472" dy="${i * 27}" class="note">${escape(note)}</text>`));
svg.push('</svg>');
writeFileSync(out('use-case-diagram.svg'), svg.join('\n') + '\n');

const plantuml = [
  '@startuml',
  'title UBE Research Repository — Updated Use Case Diagram\nCurrent implementation · 13 October 2026',
  'left to right direction',
  'skinparam shadowing false',
  'skinparam packageStyle rectangle',
  'skinparam usecaseBackgroundColor #FCFAFF',
  'skinparam usecaseBorderColor #6B4D8C',
  'actor "Guest / Public visitor" as Visitor',
  'actor "Account holder" as Account',
  'actor "Student / Faculty" as Member',
  'actor "Department Dean" as Dean',
  'actor "Research Coordinator" as Coordinator',
  'actor "Administrator" as Admin',
  'Account --|> Visitor',
  'Member --|> Account',
  'Dean --|> Account',
  'Coordinator --|> Account',
  'Admin --|> Account',
  'rectangle "UBE Research Repository" {',
];
for (const group of groups) {
  plantuml.push(`  package "${group.title}" {`);
  group.cases.forEach(([id, ...lines]) => plantuml.push(`    usecase "${lines.join('\\n')}" as ${id}`));
  plantuml.push('  }');
}
plantuml.push('}');
const actors = ['Visitor', 'Account', 'Member', 'Coordinator', 'Dean', 'Admin'];
groups.forEach((group, index) => group.cases.forEach(([id]) => plantuml.push(`${actors[index]} -- ${id}`)));
plantuml.push(
  'note right of read',
  '  Active, approved member account; current policy',
  '  accepted; active semester enrollment required.',
  'end note',
  'note bottom of submit',
  '  Submitted journals cannot be edited.',
  '  Submission, forwarding and publication each',
  '  require complete details and every journal PDF.',
  'end note',
  'note bottom of assign',
  '  Dean and Coordinator actions enforce department',
  '  scope and assignment ownership. Academic Year',
  '  and assigned program remain fixed.',
  'end note',
  'legend bottom',
  '  Actor generalization inherits public and shared account capabilities.',
  '  Current flow: Dean assigns → Coordinator submits → Dean forwards → Admin publishes.',
  '  No separate Dean approval/return step; no direct Student/Faculty submission.',
  'endlegend',
  '@enduml',
);
writeFileSync(out('use-case-diagram.puml'), plantuml.join('\n') + '\n');

const doc = [
  '# UBE Research Repository — Updated Use Case Diagram',
  '',
  'Updated 13 October 2026 from the current working tree, including uncommitted changes.',
  '',
  '![Updated use case diagram](use-case-diagram.svg)',
  '',
  '[Open SVG](use-case-diagram.svg) · [Editable PlantUML source](use-case-diagram.puml)',
  '',
  'This is a goal-level diagram of the active public, member and staff workflows. Student and Faculty share member use cases; the Faculty account uses the internal `researcher` role. Account holder is a shared actor representing all signed-in roles. PlantUML actor generalization shows inheritance of public and account capabilities; the SVG presents these shared capabilities separately for readability.',
  '',
  '## Current journal workflow',
  '',
  'Dean assigns an Academic Year/program journal task → Coordinator saves journal details and uploads finished PDFs → Coordinator submits the completed batch → Dean reviews and forwards it to Admin → Admin publishes every journal in the batch together as separate repository entries.',
  '',
  'There is no separate Dean approval, return or revision action in the current batch workflow. Submitted journals cannot be edited. Submission, forwarding and publication each require a nonempty batch with complete details and an existing PDF for every journal. Dean and Coordinator access is restricted by department and assignment ownership.',
  '',
  'Member full-document access requires an active, approved account and active semester enrollment, with the current repository policy accepted. Students and Faculty cannot submit research directly. No public self-registration route is exposed; Deans create/import member accounts and Administrators create Dean/Coordinator accounts.',
  '',
  '## Scope and source checks',
  '',
  'The diagram summarizes active goals rather than every endpoint. Existing historical submission listings and legacy single-journal compatibility paths are omitted. Legacy controller methods without a current route are not treated as active features. Batch journals are not shown with individual archive/delete actions because those endpoints reject batch members.',
  '',
  '- [Routes](../routes/web.php): public, authenticated and staff entry points.',
  '- [User role and access checks](../app/Models/User.php): staff distinctions, full-document eligibility and disabled direct submission.',
  '- [Research controller](../app/Http/Controllers/ResearchController.php): repository browsing, viewer, saved items and member dashboard.',
  '- [Admin controller](../app/Http/Controllers/AdminController.php): assignments, member imports, academic periods, accounts and reports.',
  '- [Journal batch controller](../app/Http/Controllers/JournalBatchController.php) and [workflow service](../app/Services/JournalBatchWorkflow.php): ownership, preparation and transitions.',
  '- [Activity log controller](../app/Http/Controllers/UserActivityLogController.php): Dean member logs and Administrator system logs.',
  '- [Policy middleware](../app/Http/Middleware/EnsureRepositoryPolicyAccepted.php): current policy acceptance.',
  '- [Journal workflow notes](journal-workflow.md): current batch lifecycle and report scope.',
  '',
  'Regenerate SVG and PlantUML after editing the goal definitions with `node docs/generate-use-case-diagram.mjs`.',
];
writeFileSync(out('use-case-diagram.md'), doc.join('\n') + '\n');
console.log(`Created SVG, PlantUML and Markdown documentation with ${groups.length} actor groups and ${groups.reduce((total, group) => total + group.cases.length, 0)} use cases.`);
