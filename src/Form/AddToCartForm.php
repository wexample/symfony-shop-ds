<?php

namespace Wexample\SymfonyShopDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Range;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\RadioInputType;
use Wexample\SymfonyShop\Entity\Product;
use Wexample\SymfonyShop\Entity\ProductVariation;
use Wexample\SymfonyShop\Entity\Schedule;

/**
 * A product put in the cart: one of its variations, one of its schedules,
 * how many — or, for a free amount, how much.
 */
class AddToCartForm extends AbstractForm
{
    final public const string FIELD_VARIATION = 'variation';

    final public const string FIELD_SCHEDULE = 'schedule';

    final public const string FIELD_QUANTITY = 'quantity';

    final public const string FIELD_AMOUNT = 'amount';

    final public const string OPTION_PRODUCT = 'product';

    final public const string OPTION_SCHEDULES = 'schedules';

    public static bool $ajax = true;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([self::OPTION_PRODUCT, self::OPTION_SCHEDULES]);
        $resolver->setAllowedTypes(self::OPTION_PRODUCT, Product::class);
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        /** @var Product $product */
        $product = $options[self::OPTION_PRODUCT];
        $variations = $product->getVariations()->toArray();
        $schedules = $options[self::OPTION_SCHEDULES];

        if ($variations) {
            $builder->add(self::FIELD_VARIATION, RadioInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => true,
                self::FIELD_OPTION_NAME_REQUIRED => true,
                self::FIELD_OPTION_NAME_MAPPED => false,
                'expanded' => true,
                'choices' => $variations,
                'choice_value' => static fn (?ProductVariation $variation) => $variation?->getId()?->toRfc4122(),
                'choice_label' => static fn (ProductVariation $variation) => $variation->getName().' — '.$variation->getValue(),
                'auto_translate_choices' => false,
                'data' => $variations[0],
            ]);
        }

        if ($schedules) {
            $builder->add(self::FIELD_SCHEDULE, RadioInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => true,
                self::FIELD_OPTION_NAME_REQUIRED => true,
                self::FIELD_OPTION_NAME_MAPPED => false,
                'expanded' => true,
                'choices' => $schedules,
                'choice_value' => static fn (?Schedule $schedule) => $schedule?->getId()?->toRfc4122(),
                'choice_label' => static fn (Schedule $schedule) => $schedule->getLabel() ?? $schedule->getDateStart()->format('Y-m-d H:i'),
                'auto_translate_choices' => false,
                'data' => $schedules[0],
            ]);
        }

        if ($product->isFreeAmount()) {
            $builder->add(self::FIELD_AMOUNT, NumberInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => true,
                self::FIELD_OPTION_NAME_REQUIRED => true,
                self::FIELD_OPTION_NAME_MAPPED => false,
                'scale' => 2,
                'constraints' => [new GreaterThan(0)],
            ]);
        } else {
            $builder->add(self::FIELD_QUANTITY, NumberInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => true,
                self::FIELD_OPTION_NAME_REQUIRED => true,
                self::FIELD_OPTION_NAME_MAPPED => false,
                'scale' => 0,
                'data' => 1,
                'constraints' => [new Range(min: 1, max: $product->getMaxPerCart() ?? 99)],
            ]);
        }

        $this->builderAddSubmit($builder, 'action.add');
    }
}
