// Merender model STL menjadi lembar sprite putar (WebP transparan) dengan pencahayaan studio. Lihat README.md.
// Pakai: node render.cjs <stl> <keluaran.webp> [frame=36] [lebar=144] [tinggi=192] [kolom=6] [total-derajat=90]
const fs = require('fs');
const http = require('http');
const path = require('path');
const puppeteer = require('puppeteer-core');
const [stl, out, frame = '36', lebar = '144', tinggi = '192', kolom = '6', total = '90'] = process.argv.slice(2);
if (!stl || !out) { console.error('Pakai: node render.cjs <stl> <keluaran.webp> [frame] [lebar] [tinggi] [kolom] [total-derajat]'); process.exit(1); }
const ROOT = __dirname;

const HTML = `<!doctype html><meta charset="utf-8"><body style="margin:0;background:transparent">
<script type="importmap">{"imports":{"three":"/three/build/three.module.js","three/addons/":"/three/examples/jsm/"}}</script>
<script type="module">
import * as THREE from 'three';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
import { toCreasedNormals } from 'three/addons/utils/BufferGeometryUtils.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const N = ${frame}, W = ${lebar}, H = ${tinggi}, K = ${kolom}, TOTAL = ${total}, SS = 3;
const RW = W * SS, RH = H * SS;

const buf = await (await fetch('/model.stl')).arrayBuffer();
let geo = new STLLoader().parse(buf);
geo = toCreasedNormals(geo, Math.PI / 5);
geo.computeBoundingBox();
const c = new THREE.Vector3(); geo.boundingBox.getCenter(c);
geo.translate(-c.x, -c.y, -c.z);
geo.rotateX(-Math.PI / 2); // STL: Z ke atas -> Y ke atas
geo.computeBoundingBox(); geo.computeBoundingSphere();
const bb = geo.boundingBox, tinggiModel = bb.max.y - bb.min.y, lebarModel = bb.max.x - bb.min.x, minY = bb.min.y;

const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, preserveDrawingBuffer: true });
renderer.setPixelRatio(1); renderer.setSize(RW, RH); renderer.setClearColor(0x000000, 0);
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 0.82;
renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap;
document.body.appendChild(renderer.domElement);

const scene = new THREE.Scene();
const pmrem = new THREE.PMREMGenerator(renderer);
scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture; // pantulan studio yang lembut
scene.environmentIntensity = 0.5;

// Plastik daur ulang berwarna zamrud (lapisan bening tipis agar ada kilau lembut)
const bahan = new THREE.MeshPhysicalMaterial({ color: 0x078c60, roughness: 0.42, metalness: 0.0, clearcoat: 0.22, clearcoatRoughness: 0.45 });
const mesh = new THREE.Mesh(geo, bahan); mesh.castShadow = true; mesh.receiveShadow = true;
const pivot = new THREE.Group(); pivot.add(mesh); scene.add(pivot);

const rad = geo.boundingSphere.radius;
const kunci = new THREE.DirectionalLight(0xffffff, 2.5); kunci.position.set(-rad * 0.55, rad * 4.8, rad * 1.5); // dari atas-kiri: bayangan pendek di bawah pipa
kunci.castShadow = true; kunci.shadow.mapSize.set(2048, 2048);
Object.assign(kunci.shadow.camera, { left: -rad * 1.4, right: rad * 1.4, top: rad * 1.4, bottom: -rad * 1.4, near: 0.1, far: rad * 12 });
kunci.shadow.bias = -0.0006; kunci.shadow.normalBias = rad * 0.004; kunci.shadow.radius = 4;
scene.add(kunci);
const tepi = new THREE.DirectionalLight(0xbdf5e0, 1.6); tepi.position.set(rad * 2.6, rad * 1.2, -rad * 2.8); scene.add(tepi);
scene.add(new THREE.HemisphereLight(0xe9fff6, 0x3f7f69, 0.28));

// Bidang bayangan transparan: hanya bayangan yang tampak, latar tetap transparan
const lantai = new THREE.Mesh(new THREE.PlaneGeometry(rad * 8, rad * 8), new THREE.ShadowMaterial({ opacity: 0.16 }));
lantai.rotation.x = -Math.PI / 2; lantai.position.y = minY - 0.01; lantai.receiveShadow = true; scene.add(lantai);

// Kamera potret, sedikit dari atas agar bagian dalam pipa dan lubang terlihat
const fov = 24, elev = THREE.MathUtils.degToRad(29);
const kam = new THREE.PerspectiveCamera(fov, W / H, 0.1, 6000);
const jarakTinggi = (tinggiModel * 0.60) / Math.tan(THREE.MathUtils.degToRad(fov / 2));
const jarakLebar = (lebarModel * 1.5) / (2 * Math.tan(THREE.MathUtils.degToRad(fov / 2)) * (W / H));
const jarak = Math.max(jarakTinggi, jarakLebar);
kam.position.set(0, Math.sin(elev) * jarak, Math.cos(elev) * jarak);
kam.lookAt(0, -tinggiModel * 0.02, 0);

// Titik dasar pipa di layar (untuk bayangan kontak lembut)
const dasar = new THREE.Vector3(0, minY, 0).project(kam);
const dx = ((dasar.x + 1) / 2) * W, dy = ((1 - dasar.y) / 2) * H;

const baris = Math.ceil(N / K);
const lembar = document.createElement('canvas'); lembar.width = W * K; lembar.height = H * baris;
const ctx = lembar.getContext('2d'); ctx.imageSmoothingEnabled = true; ctx.imageSmoothingQuality = 'high';

for (let i = 0; i < N; i++) {
  pivot.rotation.y = (i / N) * THREE.MathUtils.degToRad(TOTAL);
  renderer.render(scene, kam);
  const ox = (i % K) * W, oy = Math.floor(i / K) * H;
  // bayangan kontak lembut di bawah pipa
  ctx.save(); ctx.translate(ox + dx, oy + dy); ctx.scale(1, 0.26);
  const g = ctx.createRadialGradient(0, 0, 0, 0, 0, W * 0.40);
  g.addColorStop(0, 'rgba(4,70,48,0.34)'); g.addColorStop(0.6, 'rgba(4,70,48,0.12)'); g.addColorStop(1, 'rgba(4,70,48,0)');
  ctx.fillStyle = g; ctx.beginPath(); ctx.arc(0, 0, W * 0.40, 0, Math.PI * 2); ctx.fill(); ctx.restore();
  ctx.drawImage(renderer.domElement, ox, oy, W, H);
}
window.__hasil = { data: lembar.toDataURL('image/webp', 0.7), tri: geo.attributes.position.count / 3 };
window.__selesai = true;
</script>`;

const server = http.createServer((req, res) => {
  const u = decodeURIComponent(req.url.split('?')[0]);
  if (u === '/') { res.writeHead(200, { 'Content-Type': 'text/html' }); return res.end(HTML); }
  if (u === '/model.stl') { res.writeHead(200, { 'Content-Type': 'application/octet-stream' }); return fs.createReadStream(stl).pipe(res); }
  if (u.startsWith('/three/')) {
    const f = path.join(ROOT, 'node_modules', u);
    if (!f.startsWith(path.join(ROOT, 'node_modules')) || !fs.existsSync(f)) { res.writeHead(404); return res.end(); }
    res.writeHead(200, { 'Content-Type': 'text/javascript' }); return fs.createReadStream(f).pipe(res);
  }
  res.writeHead(404); res.end();
});

(async () => {
  await new Promise((ok) => server.listen(0, '127.0.0.1', ok));
  const browser = await puppeteer.launch({
    executablePath: process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: 'new',
    args: ['--no-sandbox', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'],
  });
  const page = await browser.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message.slice(0, 200)));
  page.on('console', (m) => { if (m.type() === 'error') errs.push(m.text().slice(0, 200)); });
  await page.goto(`http://127.0.0.1:${server.address().port}/`, { waitUntil: 'load' });
  await page.waitForFunction(() => window.__selesai === true, { timeout: 240000 }).catch(() => {});
  const hasil = await page.evaluate(() => window.__hasil || null);
  if (!hasil) { console.error('GAGAL render. Galat:', errs.join(' | ')); process.exit(1); }
  fs.writeFileSync(out, Buffer.from(hasil.data.split(',')[1], 'base64'));
  console.log(`ok ${out} | ${(fs.statSync(out).size / 1024).toFixed(1)} KB | segitiga ${hasil.tri} | ${frame} frame @ ${lebar}x${tinggi}px, ${kolom} kolom`);
  await browser.close(); server.close();
})();
