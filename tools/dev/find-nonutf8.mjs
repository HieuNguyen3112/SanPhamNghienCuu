#!/usr/bin/env node
import fs from "fs";
import path from "path";
import { TextDecoder } from "util";

const ROOT = path.resolve("frontend", "src");
const EXTS = new Set([
  ".vue",
  ".ts",
  ".tsx",
  ".js",
  ".jsx",
  ".css",
  ".scss",
  ".sass",
  ".json",
  ".md",
  ".html",
]);

const decoder = new TextDecoder("utf-8", { fatal: true });

function isBinary(buffer) {
  for (let i = 0; i < buffer.length; i += 1) {
    if (buffer[i] === 0x00) return true;
  }
  return false;
}

function hexSlice(buffer, start, end) {
  const slice = buffer.slice(start, end);
  return Array.from(slice)
    .map((b) => b.toString(16).padStart(2, "0"))
    .join(" ");
}

function scanFile(filePath) {
  const buf = fs.readFileSync(filePath);
  const bom = buf.length >= 3 && buf[0] === 0xef && buf[1] === 0xbb && buf[2] === 0xbf;
  const hasNull = isBinary(buf);

  try {
    decoder.decode(buf);
    return { filePath, ok: true, bom, hasNull };
  } catch (err) {
    const validUpTo = err?.validUpTo ?? null;
    const errorLen = err?.errorLength ?? null;
    const aroundStart = validUpTo !== null ? Math.max(0, validUpTo - 16) : 0;
    const aroundEnd = validUpTo !== null ? Math.min(buf.length, validUpTo + 16) : Math.min(buf.length, 32);
    const snippet = hexSlice(buf, aroundStart, aroundEnd);

    return {
      filePath,
      ok: false,
      bom,
      hasNull,
      validUpTo,
      errorLen,
      snippet,
    };
  }
}

function walk(dir, results) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    if (entry.name === "node_modules" || entry.name === "dist" || entry.name === ".git") {
      continue;
    }
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walk(fullPath, results);
      continue;
    }
    const ext = path.extname(entry.name).toLowerCase();
    if (!EXTS.has(ext)) continue;
    results.push(scanFile(fullPath));
  }
}

function main() {
  if (!fs.existsSync(ROOT)) {
    console.error(`Path not found: ${ROOT}`);
    process.exit(1);
  }

  const results = [];
  walk(ROOT, results);

  const bad = results.filter((r) => !r.ok);
  const withBom = results.filter((r) => r.ok && r.bom);
  const withNull = results.filter((r) => r.ok && r.hasNull);

  if (withBom.length > 0) {
    console.log("Files with UTF-8 BOM:");
    for (const item of withBom) {
      console.log(`  - ${path.relative(process.cwd(), item.filePath)}`);
    }
  }

  if (withNull.length > 0) {
    console.log("Files containing NUL bytes (likely binary):");
    for (const item of withNull) {
      console.log(`  - ${path.relative(process.cwd(), item.filePath)}`);
    }
  }

  if (bad.length === 0) {
    console.log("No UTF-8 decoding errors found.");
    return;
  }

  console.log("Files with UTF-8 errors:");
  for (const item of bad) {
    console.log(`  - ${path.relative(process.cwd(), item.filePath)}`);
    console.log(`    valid_up_to: ${item.validUpTo}, error_len: ${item.errorLen}`);
    console.log(`    bytes: ${item.snippet}`);
  }

  process.exitCode = 2;
}

main();
