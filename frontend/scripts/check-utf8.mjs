import fs from "node:fs";
import path from "node:path";

const root = process.argv[2] ?? "src";
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
]);
const skipDirs = new Set(["node_modules", "dist", ".git"]);
const encodingIssues = [];
const contentIssues = [];
const decoder = new TextDecoder("utf-8", { fatal: true });
const brokenVietnameseTokens = [
  "tr?nh",
  "g?i",
  "l?i",
  "yêu c?u",
  "đ?c",
  "th?nh",
  "xác nh?n",
  "t?i",
  "du?c",
  "duy?t",
  "tr?ng",
  "c?ng tr?nh",
  "hành d?ng",
];
const mojibakeChars = ["\u00C3", "\u00C2", "\u00C4", "\u00D0"];

function walk(dir) {
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
    const normalizedPath = absolutePath.replace(/\\/g, "/");
    let text = "";

    try {
      text = decoder.decode(bytes);
    } catch {
      encodingIssues.push(normalizedPath);
      continue;
    }

    if (text.includes("\uFFFD")) {
      contentIssues.push(`${normalizedPath}: contains replacement character (�)`);
      continue;
    }

    const lowered = text.toLowerCase();
    const brokenToken = brokenVietnameseTokens.find((token) =>
      lowered.includes(token)
    );
    if (brokenToken) {
      contentIssues.push(`${normalizedPath}: contains broken token "${brokenToken}"`);
      continue;
    }

    const mojibakeChar = mojibakeChars.find((char) => text.includes(char));
    if (mojibakeChar) {
      contentIssues.push(
        `${normalizedPath}: contains mojibake marker "${mojibakeChar}"`
      );
    }
  }
}

walk(root);

if (encodingIssues.length > 0) {
  console.error("Found files with invalid UTF-8 encoding:");
  for (const file of encodingIssues) {
    console.error(`- ${file}`);
  }
}

if (contentIssues.length > 0) {
  console.error("Found likely mojibake/broken Vietnamese content:");
  for (const issue of contentIssues) {
    console.error(`- ${issue}`);
  }
}

if (encodingIssues.length > 0 || contentIssues.length > 0) {
  process.exit(1);
}

console.log(`UTF-8 check passed (${root}).`);
