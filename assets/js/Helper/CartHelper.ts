// Told on the document each time the visitor's cart changed, with the cart
// as it now is: every summary and every count of the page redraw from it.
export const CART_CHANGED = 'cart:changed';

export const CART_STATE_URL = '/_cart/state';

export function cartAnnounce(state: object): void {
  document.dispatchEvent(new CustomEvent(CART_CHANGED, { detail: state }));
}

// A change posted to the cart endpoints, answered by the whole cart — also
// when refused (a line gone meanwhile, a cart frozen by its payment): the
// cart as it is is what is shown then.
export async function cartPost(url: string, fields: Record<string, string | number>): Promise<object> {
  const body = new FormData();

  Object.entries(fields).forEach(([name, value]) => body.append(name, String(value)));

  const response = await fetch(url, {
    method: 'POST',
    body,
    headers: { Accept: 'application/json' },
  });

  return response.json();
}

export async function cartRead(): Promise<object> {
  const response = await fetch(CART_STATE_URL, { headers: { Accept: 'application/json' } });

  return response.json();
}
