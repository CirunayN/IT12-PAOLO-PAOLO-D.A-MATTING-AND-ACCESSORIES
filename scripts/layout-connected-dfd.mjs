/** Lay out one connected DFD using the bundled offline Graphviz runtime. */
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const runtimeModules = 'C:/Users/charl/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules';
const { instance } = require(path.join(runtimeModules, '@viz-js/viz/dist/viz.cjs'));
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const output = path.join(root, 'output/dfd');

// One symbol per entity/store. Pinned positions preserve a coherent business
// layout while Graphviz routes the shared connections around the nodes.
const nodes = [
  ['owner', 'external', 'Admin/Owner', '', 1120, 130, 230, 75],
  ['cashier', 'external', 'Cashier', '', 165, 665, 180, 75],
  ['customer', 'external', 'Customer', '', 780, 1450, 180, 75],
  ['labor', 'external', 'Labor', '', 1370, 1440, 180, 75],
  ['P1', 'process', 'Manage Product\nRecords', '1.0', 180, 360, 270, 110],
  ['P2', 'process', 'Record Incoming\nStock', '2.0', 620, 360, 270, 110],
  ['P3', 'process', 'View Inventory', '3.0', 1040, 360, 270, 110],
  ['P4', 'process', 'Process Sales\nTransaction', '4.0', 630, 1010, 290, 120],
  ['P5', 'process', 'View Sales\nHistory', '5.0', 180, 1000, 270, 110],
  ['P6', 'process', 'Print Sales\nReceipt', '6.0', 1110, 1190, 270, 110],
  ['P7', 'process', 'Generate Sales\nReport', '7.0', 1690, 1000, 270, 110],
  ['P8', 'process', 'Generate Inventory\nReport', '8.0', 1660, 660, 270, 110],
  ['P9', 'process', 'Log In', '9.0', 1530, 340, 260, 100],
  ['P10', 'process', 'Manage User\nAccounts', '10.0', 2090, 340, 270, 110],
  ['P11', 'process', 'Create Database\nBackup', '11.0', 2150, 1040, 270, 110],
  ['P12', 'process', 'Restore Database\nBackup', '12.0', 1640, 1370, 270, 110],
  ['D1', 'store', 'Product Records', 'D1', 650, 660, 370, 80],
  ['D2', 'store', 'Product Sale Records', 'D2', 1070, 840, 340, 80],
  ['D3', 'store', 'Service Sale Records*', 'D3', 1330, 1030, 310, 80],
  ['D4', 'store', 'User Account Records', 'D4', 2050, 100, 340, 80],
  ['D5', 'store', 'Database Backup Files', 'D5', 2170, 1420, 340, 80],
  ['D6', 'store', 'System Database', 'D6', 2170, 670, 340, 80],
].map(([id, kind, title, ref, x, y, width, height])=>({id,kind,title,ref,x,y,width,height}));

const flows = [
  ['owner','P1','New/changed product details'],
  ['D1','P1','Existing product records'],
  ['P1','D1','Saved product changes'],
  ['owner','P2','Shipment and received quantity'],
  ['D1','P2','Product/current stock'],
  ['P2','D1','New stock batch'],
  ['owner','P3','Inventory filters'],
  ['D1','P3','Products/prices/stock'],
  ['P3','owner','Filtered inventory'],
  ['owner','P4','Sales details'],
  ['cashier','P4','Items/quantity/payment'],
  ['customer','P4','Order/payment details'],
  ['D1','P4','Sellable items/stock'],
  ['P4','D1','Deducted stock quantities'],
  ['P4','D2','Recorded sale/payment'],
  ['labor','P4','Service progress/status*'],
  ['P4','labor','Service job details*'],
  ['P4','D3','Service charge/status*'],
  ['P4','P6','Completed sale ID'],
  ['owner','P5','History filters'],
  ['cashier','P5','Own-sales filters'],
  ['D2','P5','Authorized sales records'],
  ['P5','owner','Sales history'],
  ['P5','cashier','Own sales history'],
  ['owner','P6','Receipt request'],
  ['cashier','P6','Receipt request'],
  ['D2','P6','Sale/items/payment/change'],
  ['D3','P6','Linked service charges*'],
  ['P6','owner','Printable receipt'],
  ['P6','cashier','Printable receipt'],
  ['P6','customer','Purchase receipt'],
  ['owner','P7','Sales dates/filters/output'],
  ['D2','P7','Period sales data'],
  ['D3','P7','Period service data*'],
  ['P7','owner','Sales report/print/PDF'],
  ['owner','P8','Stock filters/output'],
  ['D1','P8','Stock/threshold data'],
  ['P8','owner','Inventory report/print/PDF'],
  ['owner','P9','Username/password'],
  ['cashier','P9','Username/password'],
  ['D4','P9','Credentials/role/access'],
  ['P9','D4','Authenticated session'],
  ['P9','owner','Admin login result'],
  ['P9','cashier','Cashier login result'],
  ['owner','P10','User details/access choice'],
  ['D4','P10','User/admin records'],
  ['P10','D4','Created/updated user account'],
  ['owner','P11','Backup request'],
  ['D6','P11','Current database data'],
  ['P11','D5','New database backup'],
  ['owner','P12','Backup/admin password'],
  ['D5','P12','Selected backup contents'],
  ['D4','P12','Admin credentials'],
  ['P12','D6','Restored database records'],
  ['P12','owner','Restoration result'],
].map(([source,target,label],index)=>({id:`flow-${String(index+1).padStart(2,'0')}`,source,target,label}));

const graph = {
  directed:true,
  graphAttributes:{splines:'ortho', overlap:'true', outputorder:'edgesfirst',
    bgcolor:'white', fontname:'Arial', pad:'0.3', forcelabels:'true', notranslate:'true'},
  nodeAttributes:{shape:'box', fontname:'Arial', fontsize:'19', color:'black',
    fillcolor:'white', style:'filled', fixedsize:'true', pin:'true', margin:'0.03'},
  edgeAttributes:{fontname:'Arial', fontsize:'13', color:'black', arrowsize:'0.85'},
  nodes:nodes.map(n=>({name:n.id,attributes:{
    label:n.kind==='process'?`${n.ref}\n${n.title}`:n.kind==='store'?`${n.ref}  ${n.title}`:n.title,
    pos:`${n.x/72},${-n.y/72}!`, width:String(n.width/72),height:String(n.height/72),
  }})),
  edges:flows.map(e=>({tail:e.source,head:e.target,attributes:{id:e.id,xlabel:e.label}})),
};
const viz=await instance();
const layout=viz.renderJSON(graph,{engine:'neato'});
fs.mkdirSync(output,{recursive:true});
fs.writeFileSync(path.join(output,'Level-0-DFD-Redone-layout.json'),JSON.stringify({nodes,flows,layout},null,2));
console.log(JSON.stringify({nodes:nodes.length,processes:nodes.filter(n=>n.kind==='process').length,flows:flows.length,bounds:layout.bb}));
