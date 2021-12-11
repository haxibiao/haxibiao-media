export async function exceptionCapture(fn: () => Promise<any>) {
  try {
    const res = await fn();
    return [null, res];
  } catch (err) {
    return [err, null];
  }
}
