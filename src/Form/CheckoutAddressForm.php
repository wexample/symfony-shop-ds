<?php

namespace Wexample\SymfonyShopDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\EmailInputType;
use Wexample\SymfonyForms\Form\Type\TelInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyGeoDs\Form\Type\PostalAddressType;
use Wexample\SymfonyShopDs\Form\Data\CheckoutAddresses;

/**
 * The addresses of an order: the buyer's email when they are not signed in,
 * their phone, their company and VAT number when they buy as a business, the
 * billing address, and — only when something is shipped — where to, the
 * billing one when left empty.
 */
class CheckoutAddressForm extends AbstractForm
{
    final public const string OPTION_ASK_EMAIL = 'ask_email';

    final public const string OPTION_SHIPPING = 'shipping';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => CheckoutAddresses::class,
            self::OPTION_ASK_EMAIL => false,
            self::OPTION_SHIPPING => false,
        ]);
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        if ($options[self::OPTION_ASK_EMAIL]) {
            $builder->add('email', EmailInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => true,
                self::FIELD_OPTION_NAME_REQUIRED => true,
                'constraints' => [new NotBlank(), new Email()],
            ]);
        }

        $optional = [self::FIELD_OPTION_NAME_LABEL => true, self::FIELD_OPTION_NAME_REQUIRED => false];

        $builder
            ->add('phone', TelInputType::class, $optional)
            ->add('companyName', TextInputType::class, $optional)
            ->add('vatNumber', TextInputType::class, $optional);

        $builder->add('billing', PostalAddressType::class, ['with_name' => true, 'required' => true]);

        if ($options[self::OPTION_SHIPPING]) {
            $builder->add('shipping', PostalAddressType::class, ['with_name' => true, 'required' => false]);
        }
    }
}
