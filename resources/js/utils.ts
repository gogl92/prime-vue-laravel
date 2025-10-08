import { twMerge } from 'tailwind-merge';
import { mergeProps } from 'vue';

export const ptViewMerge = (
    globalPTProps = {} as Record<string, unknown>,
    selfPTProps = {} as Record<string, unknown>,
    datasets: Record<string, unknown>,
) => {
    const { class: globalClass, ...globalRest } = globalPTProps;
    const { class: selfClass, ...selfRest } = selfPTProps;

    return mergeProps(
        { class: twMerge(globalClass, selfClass) },
        globalRest,
        selfRest,
        datasets,
    );
};
