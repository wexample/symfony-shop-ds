# Produits numériques : le mail au client et les liens dans « mes commandes »

Opened: 2026-10-12
Updated: 2026-10-12
Author: agent:main

Le backend est fait (`symfony-shop`, section « Digital products » de l'usage ; `symfony-media`, section « Restricted media and access grants »). Il reste deux petites choses côté interface, sans rien d'autre à construire.

## À faire

1. **Le mail.** Écouter `Wexample\SymfonyShop\Event\DigitalGoodsDeliveredEvent` et écrire au client, comme `Service/CartReminderService.php` le fait déjà : l'adresse du compte, sinon celle saisie dans le tunnel (`Cart::getCustomerEmail()`), avec `MailSenderService`. Une ligne par fichier : son nom (`$grant->getMedia()->getName()`), son lien (`MediaAccessService::getUrl($grant)`), sa date de fin (`getExpiresAt()`) et le nombre de téléchargements permis (`getMaxUses()`). Les textes dans le `.trans.yml` du mail.
2. **« Mes commandes ».** Sur la fiche d'une commande (`Controller/Pages/OrdersController.php`), pour chaque ligne d'un produit numérique, les liens encore valables : `DigitalDeliveryHandler::getGrants($item)`, filtrés par `isUsable()`, chacun avec son `getUrl()`. Un lien épuisé ou expiré s'affiche comme tel, sans lien.
3. **Démo.** Un produit numérique dans `symfony-shop-demo` (un PDF d'exemple en anglais), pour voir le parcours jusqu'au téléchargement.
