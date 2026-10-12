# Panier : l'interface (-ds), la démo (-demo), et les restes du backend

Opened: 2026-10-09
Author: agent:main

## Closed (2026-10-12)

Done: shop, product page, `cart_add`, `cart_summary`, header `cart_button` with its panel, checkout tunnel (guest or account through symfony-user's steps, cart adopted at sign-in), stock hold countdown, abandoned-cart reminders (`shop-ds:remind`, signed resume link), my orders. Package renamed from symfony-cart-ds to symfony-shop-ds. The owner dropped the product administration: applications fill their catalogue their own way.

Une todo pour `symfony-shop-ds` et `symfony-shop-demo`, vides à ce jour, et pour ce qui manque encore à `symfony-shop`. Le backend est fait et publié : s'en servir, le compléter là où l'interface en a besoin.

**À faire après `symfony-payment-ds`** : son étape de paiement est réutilisée ici (`symfony-payment-ds/.wex/journal/todo/payment-interface-and-demo.md`).

## À lire d'abord (plutôt que de le répéter ici)

- `symfony-shop/.wex/knowledge/usage/overview.md.j2` : remplir un panier, checkout.
- `symfony-shop/.wex/journal/todo/extract-from-network.md` : section Status (ce qui est fait) et le détail de ce qui reste (#301, plannings, adresses, tunnel).
- `symfony-tunnels/.wex/knowledge/usage/overview.md.j2`, et `symfony-tunnels-demo` comme exemple de tunnel sur le design system.
- `symfony-payment-ds/.wex/journal/todo/payment-interface-and-demo.md` et `symfony-payment/.wex/knowledge/usage/overview.md.j2`.
- `symfony-money` (formatage des prix, `price` en Twig) et `symfony-geo` (adresses, relation `Country`).
- L'archéologie, en lecture seule : `NETWORK/local/network/.wex/knowledge/readme/archeology/cart-payment.md.j2` (le tunnel produit unique de network, `SingleProductTunnelControllerTest`, adhésions, campagnes) ; l'état d'ensemble dans `status.md.j2`. Ne rien exécuter dans network : données de prod.
- `symfony-accounting/.wex/journal/todo/5a40fb66c266.md` : règles du propriétaire et pièges de MOJOE.

## Ce que -ds devrait fournir (à discuter avec le propriétaire avant de coder)

- **Catalogue** : liste et fiche produit, variations, disponibilité et créneaux (`Schedule`, `ProductAvailability`), stock restant.
- **Panier** : contenu, quantités, retrait, total, expiration visible ; un accès au panier depuis le layout (à la manière du compteur de notifications).
- **Tunnel d'achat** sur symfony-tunnels : panier → adresse(s) (`CartAddress`) → paiement (l'étape de payment-ds) → confirmation ; reprise après le retour du prestataire.
- **Mes commandes** : paniers payés, détail, statut.
- **Administration** : produits, variations, disponibilités (#301), stocks, ventes par produit ; formulaires symfony-forms, rôle configurable comme les écrans d'administration de symfony-user-ds.

## Restes du backend à faire en chemin

- Formulaires d'administration des variations et disponibilités (#301).
- Plannings récurrents : `Schedule.rule` (RRULE) est stocké mais pas interprété.
- Copier les adresses du panier dans le carnet de l'utilisateur au paiement, si l'application en a un.
- Le tunnel d'achat en test d'intégration : un scénario symfony-testing (`.wex/knowledge/usage/scenarios.md.j2`) — acheteur, visiteur, administrateur —, comme `tests/Scenario/` de MOJOE design system.

## La démo

- Sur les comptes de `symfony-user-demo` ; pas d'utilisateurs propres.
- Un petit catalogue fictif (un produit simple, un à variations, un à créneaux limités), payé avec le `FakePaymentProvider` de remote-payment : aucun compte Stripe.
- Montrer un achat complet, un stock épuisé, un panier expiré, un paiement échoué puis repris.
- Rédigée en anglais ; `.trans.yml` en `en:` seulement.

## Règles

- Seulement les composants du design system (pas de HTML brut ni de CSS propre) ; actions de page en `page_title_actions`, modales et panneaux du loader.
- Des relations partout (pays, devise), pas de code ISO hors de leur table.
- À vérifier dans MOJOE design system (`MOJOE/local/design-system`), sans commiter ce dépôt : il appartient au propriétaire.
