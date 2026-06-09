import { motion, type HTMLMotionProps } from 'framer-motion';
import { useReducedMotion } from './useReducedMotion';
import { fadeUp } from './variants';

interface AnimateInViewProps extends HTMLMotionProps<'div'> {
    once?: boolean;
}

export function AnimateInView({ children, once = true, ...props }: AnimateInViewProps) {
    const reducedMotion = useReducedMotion();

    if (reducedMotion) {
        return <div {...props}>{children}</div>;
    }

    return (
        <motion.div
            initial="hidden"
            whileInView="visible"
            viewport={{ once, margin: '-80px' }}
            variants={fadeUp}
            {...props}
        >
            {children}
        </motion.div>
    );
}
