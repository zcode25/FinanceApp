import { CheckCircle2, Zap, Rocket, Crown } from 'lucide-vue-next';
import { __ } from '@/Plugins/i18n';

export const plansData = [
    {
        id: 1,
        name: 'Starter',
        period: '/forever',
        description: 'starter_plan_desc',
        icon: CheckCircle2,
        color: 'slate',
        features: [
            'feature_3_wallets',
            'feature_3_categories',
            'feature_3_months',
            'feature_dashboard',
            'feature_standard_budgeting'
        ],
        buttonText: 'get_started'
    },
    {
        id: 2,
        name: 'Professional',
        period: '/month',
        description: 'pro_plan_desc',
        icon: Rocket,
        color: 'indigo',
        features: [
            'feature_unlimited_wallets',
            'feature_ai_recs',
            'feature_full_history',
            'feature_exports',
            'feature_ledger'
        ],
        buttonText: 'get_professional'
    },
    {
        id: 3,
        name: 'Master',
        period: '/year',
        description: 'master_plan_desc',
        icon: Crown,
        color: 'emerald',
        popular: true,
        features: [
            'feature_pro_included',
            'feature_unlimited_categories',
            'feature_priority_support',
            'feature_verified_badge',
            'feature_save_17'
        ],
        buttonText: 'get_master'
    },
    {
        id: 4,
        name: 'Lifetime',
        period: 'once',
        description: 'lifetime_plan_desc',
        icon: Zap,
        color: 'purple',
        features: [
            'feature_master_included',
            'feature_lifetime_updates',
            'feature_founder_status',
            'feature_early_access',
            'feature_one_time'
        ],
        buttonText: 'buy_lifetime'
    }
];

export const formatPrice = (price) => {
    if (price === 0) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
};

export const getLocalizedPlans = (dbPlans) => {
    return plansData.map(pData => {
        const dbPlan = dbPlans?.find(p => parseInt(p.id) === parseInt(pData.id));
        return {
            ...pData,
            name: dbPlan?.name ?? pData.name,
            price: formatPrice(dbPlan?.price ?? 0),
            rawPrice: dbPlan?.price ?? 0,
            description: __(pData.description),
            buttonText: __(pData.buttonText),
            features: pData.features.map(f => __(f))
        };
    });
};
