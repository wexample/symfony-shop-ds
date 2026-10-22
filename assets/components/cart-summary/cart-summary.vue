<script>
import Message from '@wexample/symfony-design-system/components/message/message.vue';
import { CART_CHANGED, cartAnnounce, cartPost } from '../../js/Helper/CartHelper';

const T = 'WexampleSymfonyShopDsBundle.components.cart-summary.cart-summary::';

// The visitor's cart, line by line, and its total: on the cart page, where a
// line can change (`editable`), in the header's panel, beside the tunnel —
// read only there. Every summary and every count on the page show the same
// cart: a change made in one is told to all (`cart:changed`). While it is
// being paid, how long its stock is still held for it.
export default {
  template: '#vue-template-wexample-symfony-shop-ds-bundle-components-cart-summary-cart-summary',

  components: {
    Message
  },

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
    // The visitor's cart, followed as it changes; an order is not.
    live: {
      type: Boolean,
      default: true
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
      busy: false,
      now: Date.now()
    };
  },

  computed: {
    canEdit() {
      return this.editable && this.cart.editable && !!this.csrfToken;
    },

    reservation() {
      if (!this.cart.reserved_until) {
        return null;
      }

      const left = Math.max(0, Math.floor((Date.parse(this.cart.reserved_until) - this.now) / 1000));
      const minutes = Math.floor(left / 60);
      const seconds = String(left % 60).padStart(2, '0');

      return { over: left === 0, time: `${minutes}:${seconds}` };
    }
  },

  mounted() {
    if (this.live) {
      document.addEventListener(CART_CHANGED, this.onChanged);
    }

    this.clock = setInterval(() => {
      this.now = Date.now();
    }, 1000);
  },

  beforeUnmount() {
    document.removeEventListener(CART_CHANGED, this.onChanged);
    clearInterval(this.clock);
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
