/**
 * Buka chatbot AI Mentor dari halaman mana pun, mis. tombol "Tanya AI Mentor"
 * di Learning Gap yang membawa konteks kompetensi (CLAUDE.md bagian 6.3).
 */
export type KonteksMentor = { kompetensiId: number; nama: string };

export const EVENT_BUKA_MENTOR = 'ai-mentor:buka';

export function bukaAiMentor(konteks?: KonteksMentor): void {
    window.dispatchEvent(
        new CustomEvent<KonteksMentor | undefined>(EVENT_BUKA_MENTOR, {
            detail: konteks,
        }),
    );
}
