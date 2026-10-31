/**
 * Generate a collision-resistant node id.
 */
export function createId(): string {
  const bytes = new Uint8Array(5);

  if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
    crypto.getRandomValues(bytes);
  } else {
    for (let index = 0; index < bytes.length; index += 1) {
      bytes[index] = Math.floor(Math.random() * 256);
    }
  }

  return (
    'n' +
    Array.from(bytes)
      .map((byte) => byte.toString(16).padStart(2, '0'))
      .join('')
  );
}
