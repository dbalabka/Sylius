<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Component\Promotion\Generator;

use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Component\Promotion\Exception\FailedGenerationException;
use Sylius\Component\Promotion\Model\CouponInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;
use Sylius\Component\Promotion\Repository\CouponRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

/**
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class CouponGenerator implements CouponGeneratorInterface
{
    /**
     * @var FactoryInterface
     */
    protected $couponFactory;

    /**
     * @var CouponRepositoryInterface
     */
    protected $couponRepository;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var GenerationPolicyInterface
     */
    protected $generationPolicy;

    /**
     * @var CouponInterface[]
     */
    protected $generatedCoupons = [];

    /**
     * @param FactoryInterface $couponFactory
     * @param CouponRepositoryInterface $couponRepository
     * @param ObjectManager $objectManager
     * @param GenerationPolicyInterface $generationPolicy
     */
    public function __construct(
        FactoryInterface $couponFactory,
        CouponRepositoryInterface $couponRepository,
        ObjectManager $objectManager,
        GenerationPolicyInterface $generationPolicy
    ) {
        $this->couponFactory = $couponFactory;
        $this->couponRepository = $couponRepository;
        $this->objectManager = $objectManager;
        $this->generationPolicy = $generationPolicy;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(PromotionInterface $promotion, InstructionInterface $instruction)
    {
        $this->generatedCoupons = [];

        $this->assertGenerationIsPossible($instruction);
        for ($i = 0, $amount = $instruction->getAmount(); $i < $amount; ++$i) {
            $code = $this->generateUniqueCode($instruction->getCodeLength());
            $coupon = $this->couponFactory->createNew();
            $coupon->setPromotion($promotion);
            $coupon->setCode($code);
            $coupon->setUsageLimit($instruction->getUsageLimit());
            $coupon->setExpiresAt($instruction->getExpiresAt());

            $this->generatedCoupons[$code] = true;

            $this->objectManager->persist($coupon);
        }

        $this->objectManager->flush();

        return $this->generatedCoupons;
    }


    /**
     * @param int $codeLength
     *
     * @return string
     */
    protected function generateUniqueCode($codeLength)
    {
        Assert::nullOrRange($codeLength, 1, 40, 'Invalid %d code length should be between %d and %d');

        do {
            $hash = sha1(microtime(true));
            $code = strtoupper(substr($hash, 0, $codeLength));
        } while ($this->isUsedCode($code));

        return $code;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    protected function isUsedCode($code)
    {
        if (isset($this->generatedCoupons[$code])) {
            return true;
        }

        return null !== $this->couponRepository->findOneBy(['code' => $code]);
    }

    /**
     * @param InstructionInterface $instruction
     *
     * @throws FailedGenerationException
     */
    private function assertGenerationIsPossible(InstructionInterface $instruction)
    {
        if (!$this->generationPolicy->isGenerationPossible($instruction)) {
            throw new FailedGenerationException($instruction);
        }
    }
}
