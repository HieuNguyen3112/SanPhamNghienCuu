import fs from "node:fs";
import path from "node:path";

const inputPaths = process.argv.slice(2);
const roots = inputPaths.length > 0 ? inputPaths : ["src"];
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
const skipDirs = new Set(["node_modules", "dist", ".git", ".vite", "public"]);

const invalidUtf8Issues = [];
const mojibakeIssues = [];
const bomWarnings = [];
let scannedFileCount = 0;

function normalizePath(p) {
  return p.replace(/\\/g, "/");
}

function toHexByte(value) {
  return `0x${value.toString(16).toUpperCase().padStart(2, "0")}`;
}

function isContinuationByte(byte) {
  return (byte & 0xc0) === 0x80;
}

function validateUtf8(bytes) {
  let i = 0;
  while (i < bytes.length) {
    const b1 = bytes[i];
    if (b1 <= 0x7f) {
      i += 1;
      continue;
    }

    if (b1 >= 0xc2 && b1 <= 0xdf) {
      if (i + 1 >= bytes.length) {
        return { offset: i, byte: b1, reason: "truncated 2-byte sequence" };
      }
      const b2 = bytes[i + 1];
      if (!isContinuationByte(b2)) {
        return {
          offset: i + 1,
          byte: b2,
          reason: "invalid continuation byte for 2-byte sequence",
        };
      }
      i += 2;
      continue;
    }

    if (b1 === 0xe0) {
      if (i + 2 >= bytes.length) {
        return { offset: i, byte: b1, reason: "truncated 3-byte sequence" };
      }
      const b2 = bytes[i + 1];
      const b3 = bytes[i + 2];
      if (b2 < 0xa0 || b2 > 0xbf) {
        return {
          offset: i + 1,
          byte: b2,
          reason: "invalid second byte for 3-byte sequence (E0)",
        };
      }
      if (!isContinuationByte(b3)) {
        return {
          offset: i + 2,
          byte: b3,
          reason: "invalid continuation byte for 3-byte sequence",
        };
      }
      i += 3;
      continue;
    }

    if ((b1 >= 0xe1 && b1 <= 0xec) || (b1 >= 0xee && b1 <= 0xef)) {
      if (i + 2 >= bytes.length) {
        return { offset: i, byte: b1, reason: "truncated 3-byte sequence" };
      }
      const b2 = bytes[i + 1];
      const b3 = bytes[i + 2];
      if (!isContinuationByte(b2)) {
        return {
          offset: i + 1,
          byte: b2,
          reason: "invalid second byte for 3-byte sequence",
        };
      }
      if (!isContinuationByte(b3)) {
        return {
          offset: i + 2,
          byte: b3,
          reason: "invalid continuation byte for 3-byte sequence",
        };
      }
      i += 3;
      continue;
    }

    if (b1 === 0xed) {
      if (i + 2 >= bytes.length) {
        return { offset: i, byte: b1, reason: "truncated 3-byte sequence" };
      }
      const b2 = bytes[i + 1];
      const b3 = bytes[i + 2];
      if (b2 < 0x80 || b2 > 0x9f) {
        return {
          offset: i + 1,
          byte: b2,
          reason: "invalid second byte for 3-byte sequence (ED)",
        };
      }
      if (!isContinuationByte(b3)) {
        return {
          offset: i + 2,
          byte: b3,
          reason: "invalid continuation byte for 3-byte sequence",
        };
      }
      i += 3;
      continue;
    }

    if (b1 === 0xf0) {
      if (i + 3 >= bytes.length) {
        return { offset: i, byte: b1, reason: "truncated 4-byte sequence" };
      }
      const b2 = bytes[i + 1];
      const b3 = bytes[i + 2];
      const b4 = bytes[i + 3];
      if (b2 < 0x90 || b2 > 0xbf) {
        return {
          offset: i + 1,
          byte: b2,
          reason: "invalid second byte for 4-byte sequence (F0)",
        };
      }
      if (!isContinuationByte(b3)) {
        return {
          offset: i + 2,
          byte: b3,
          reason: "invalid continuation byte for 4-byte sequence",
        };
      }
      if (!isContinuationByte(b4)) {
        return {
          offset: i + 3,
          byte: b4,
          reason: "invalid continuation byte for 4-byte sequence",
        };
      }
      i += 4;
      continue;
    }

    if (b1 >= 0xf1 && b1 <= 0xf3) {
      if (i + 3 >= bytes.length) {
        return { offset: i, byte: b1, reason: "truncated 4-byte sequence" };
      }
      const b2 = bytes[i + 1];
      const b3 = bytes[i + 2];
      const b4 = bytes[i + 3];
      if (!isContinuationByte(b2)) {
        return {
          offset: i + 1,
          byte: b2,
          reason: "invalid second byte for 4-byte sequence",
        };
      }
      if (!isContinuationByte(b3)) {
        return {
          offset: i + 2,
          byte: b3,
          reason: "invalid continuation byte for 4-byte sequence",
        };
      }
      if (!isContinuationByte(b4)) {
        return {
          offset: i + 3,
          byte: b4,
          reason: "invalid continuation byte for 4-byte sequence",
        };
      }
      i += 4;
      continue;
    }

    if (b1 === 0xf4) {
      if (i + 3 >= bytes.length) {
        return { offset: i, byte: b1, reason: "truncated 4-byte sequence" };
      }
      const b2 = bytes[i + 1];
      const b3 = bytes[i + 2];
      const b4 = bytes[i + 3];
      if (b2 < 0x80 || b2 > 0x8f) {
        return {
          offset: i + 1,
          byte: b2,
          reason: "invalid second byte for 4-byte sequence (F4)",
        };
      }
      if (!isContinuationByte(b3)) {
        return {
          offset: i + 2,
          byte: b3,
          reason: "invalid continuation byte for 4-byte sequence",
        };
      }
      if (!isContinuationByte(b4)) {
        return {
          offset: i + 3,
          byte: b4,
          reason: "invalid continuation byte for 4-byte sequence",
        };
      }
      i += 4;
      continue;
    }

    return { offset: i, byte: b1, reason: "invalid leading byte" };
  }

  return null;
}

function collectFiles(targetPath) {
  const absolutePath = path.resolve(targetPath);
  if (!fs.existsSync(absolutePath)) return [];

  const stats = fs.statSync(absolutePath);
  if (stats.isFile()) {
    const ext = path.extname(absolutePath).toLowerCase();
    return textExtensions.has(ext) ? [absolutePath] : [];
  }

  const collected = [];
  for (const entry of fs.readdirSync(absolutePath, { withFileTypes: true })) {
    if (entry.isDirectory() && skipDirs.has(entry.name)) continue;
    const child = path.join(absolutePath, entry.name);
    if (entry.isDirectory()) {
      collected.push(...collectFiles(child));
      continue;
    }
    const ext = path.extname(entry.name).toLowerCase();
    if (textExtensions.has(ext)) collected.push(child);
  }
  return collected;
}

function firstMojibakeMatch(text) {
  const patterns = [/Ã./u, /Ä./u, /â€./u, /ï¿½/u, /\uFFFD/u];
  for (const pattern of patterns) {
    const match = pattern.exec(text);
    if (match) return { index: match.index, value: match[0] };
  }
  return null;
}

const files = [...new Set(roots.flatMap((entry) => collectFiles(entry)))];

for (const filePath of files) {
  scannedFileCount += 1;
  const bytes = fs.readFileSync(filePath);
  const normalizedPath = normalizePath(path.relative(process.cwd(), filePath));

  if (
    bytes.length >= 3 &&
    bytes[0] === 0xef &&
    bytes[1] === 0xbb &&
    bytes[2] === 0xbf
  ) {
    bomWarnings.push(`${normalizedPath}: UTF-8 BOM detected at byte offset 0`);
  }

  const utf8Issue = validateUtf8(bytes);
  if (utf8Issue) {
    invalidUtf8Issues.push(
      `${normalizedPath}: invalid UTF-8 at byte offset ${utf8Issue.offset} (${toHexByte(
        utf8Issue.byte
      )}) - ${utf8Issue.reason}`
    );
    continue;
  }

  const text = bytes.toString("utf8");
  const mojibakeMatch = firstMojibakeMatch(text);
  if (mojibakeMatch) {
    const byteOffset = Buffer.byteLength(text.slice(0, mojibakeMatch.index), "utf8");
    mojibakeIssues.push(
      `${normalizedPath}: suspicious mojibake "${mojibakeMatch.value}" at byte offset ${byteOffset}`
    );
  }
}

if (invalidUtf8Issues.length > 0) {
  console.error("Found files with invalid UTF-8 bytes:");
  for (const issue of invalidUtf8Issues) console.error(`- ${issue}`);
}

if (mojibakeIssues.length > 0) {
  console.error("Found files with suspicious mojibake markers:");
  for (const issue of mojibakeIssues) console.error(`- ${issue}`);
}

if (bomWarnings.length > 0) {
  console.warn("Found UTF-8 BOM warnings:");
  for (const warning of bomWarnings) console.warn(`- ${warning}`);
}

if (invalidUtf8Issues.length > 0 || mojibakeIssues.length > 0) {
  process.exit(1);
}

console.log(
  `UTF-8 check passed. Scanned ${scannedFileCount} file(s): ${roots.join(", ")}`
);
