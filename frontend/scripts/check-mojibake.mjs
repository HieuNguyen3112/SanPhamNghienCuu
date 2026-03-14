import fs from "node:fs";
import path from "node:path";

const inputPaths = process.argv.slice(2);
const roots = inputPaths.length > 0
  ? inputPaths
  : [
      "src",
      "../backend/app",
      "../backend/routes",
      "../backend/tests",
      "../backend/database/seeders",
      "../backend/resources/views",
    ];
const textExtensions = new Set([
  ".vue",
  ".ts",
  ".tsx",
  ".js",
  ".jsx",
  ".json",
  ".css",
  ".scss",
  ".md",
  ".php",
]);
const skipDirs = new Set(["node_modules", "dist", ".git", "vendor", "storage", "bootstrap"]);
const decoder = new TextDecoder("utf-8", { fatal: true });
const issues = [];

const mojibakeMarkers = [
  String.fromCharCode(0x00c3), // Ã
  String.fromCharCode(0x00c2), // Â
  String.fromCharCode(0x00c4), // Ä
  "\u00e2\u20ac", // â€
];

function walk(dir) {
  if (!fs.existsSync(dir)) return;

  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (skipDirs.has(entry.name)) continue;
    const absolutePath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      walk(absolutePath);
      continue;
    }

    const ext = path.extname(entry.name).toLowerCase();
    if (!textExtensions.has(ext)) continue;

    const bytes = fs.readFileSync(absolutePath);
    let text = "";

    try {
      text = decoder.decode(bytes);
    } catch {
      issues.push(`${absolutePath.replace(/\\/g, "/")}: invalid UTF-8`);
      continue;
    }

    for (const marker of mojibakeMarkers) {
      const index = text.indexOf(marker);
      if (index === -1) continue;

      const normalizedPath = absolutePath.replace(/\\/g, "/");
      const preview = text.slice(Math.max(0, index - 20), index + 40).replace(/\s+/g, " ");
      issues.push(
        `${normalizedPath}: found mojibake marker "${marker}" near "${preview}"`
      );
      break;
    }
  }
}

for (const root of roots) {
  walk(root);
}

if (issues.length > 0) {
  console.error("Mojibake check failed:");
  for (const issue of issues) {
    console.error(`- ${issue}`);
  }
  process.exit(1);
}

console.log("Mojibake check passed.");
