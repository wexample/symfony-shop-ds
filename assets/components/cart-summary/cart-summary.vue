<script>
import { CART_CHANGED, cartAnnounce, cartPost } from '../../js/Helper/CartHelper';

const T = 'WexampleSymfonyCartDsBundle.components.cart-summary.cart-summary::';

// The visitor's cart, line by line, and its total: on the cart page, where a
// line can change (`editable`), in the header's panel, beside the tunnel —
// read only there. Every summary and every count on the page show the same
// cart: a change made in one is told to all (`cart:changed`).
export default {
  template: '#vue-template-wexample-symfony-cart-ds-bundle-components-cart-summary-cart-summary',

  props: {
    state: {
      type: Object,
      required: true
    },
    csrfToken: {
      type: String,
      default: null
    },
    editable: {
      type: Boolean,
      default: false
    },
    // Its own title, where nothing above says it is the cart.
    title: {
      type: Boolean,
      default: false
    },
    compact: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      cart: this.state,
      busy: false
    };
  },

  computed: {
    canEdit() {
      return this.editable && this.cart.editable && !!this.csrfToken;
    }
  },

  mounted() {
    document.addEventListener(CART_CHANGED, this.onChanged);
  },

  beforeUnmount() {
    document.removeEventListener(CART_CHANGED, this.onChanged);
  },

  methods: {
    t(key, parameters = {}) {
      return this.trans(`${T}${key}`, parameters);
    },

    icon(name) {
      return this.app.getServiceOrFail('icon').icon(name);
    },

    onChanged(event) {
      this.cart = event.detail;
    },

    async setQuantity(item, quantity) {
      await this.send(`/_cart/item/${item.id}/quantity`, { quantity });
    },

    async remove(item) {
      await this.send(`/_cart/item/${item.id}/remove`);
    },

    async send(url, fields = {}) {
      this.busy = true;

      try {
        cartAnnounce(await cartPost(url, { _token: this.csrfToken, ...fields }));
      } finally {
        this.busy = false;
      }
    }
  }
};
</script>
