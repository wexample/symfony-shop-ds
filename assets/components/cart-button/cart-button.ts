import Component from '@wexample/symfony-loader/js/Class/Component';
import { formSuccessEvent } from '@wexample/symfony-loader/js/Constants/FormEvents';
import { loadIntoTarget } from '@wexample/symfony-design-system/js/Helper/TargetHelper';
import { CART_CHANGED, cartAnnounce, cartRead } from '../../js/Helper/CartHelper';

// The form putting a product in the cart, by the name the server knows it by.
const ADD_TO_CART_FORM = 'add_to_cart_form';

export default class extends Component {
  protected async activateListeners(): Promise<void> {
    await super.activateListeners();

    this.el.addEventListener('click', this.onClick);
    document.addEventListener(CART_CHANGED, this.onChanged);
    document.addEventListener(formSuccessEvent(ADD_TO_CART_FORM), this.onAdded);
  }

  protected async deactivateListeners(): Promise<void> {
    this.el.removeEventListener('click', this.onClick);
    document.removeEventListener(CART_CHANGED, this.onChanged);
    document.removeEventListener(formSuccessEvent(ADD_TO_CART_FORM), this.onAdded);

    await super.deactivateListeners();
  }

  private onClick = (event: MouseEvent): void => {
    // A modified click stays a navigation: the cart as a page, in a new tab.
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
      return;
    }

    event.preventDefault();
    loadIntoTarget(this.app, 'panel', (this.el as HTMLAnchorElement).getAttribute('href')!, {
      closeOnEscape: true,
      closeOnOverlayClick: true,
    });
  };

  private onChanged = (event: Event): void => {
    const count = (event as CustomEvent).detail?.count ?? 0;
    const badge = this.el.querySelector<HTMLElement>('.cart-button--count')!;

    badge.textContent = String(count);
    badge.hidden = !count;
  };

  // Something was put in the cart somewhere on the page: the cart is read
  // again, and told to everything showing it.
  private onAdded = async (): Promise<void> => {
    cartAnnounce(await cartRead());
  };
}
